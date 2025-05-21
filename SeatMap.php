<?php
require_once 'Seat.php';
require_once 'Database.php';

class SeatMap {
    public $seats = [];
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->generateSeats();
        $this->loadBookings();
    }

    private function generateSeats() {
        $rows = range(1, 10);
        $columns = range('A', 'I');

        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $id = $col . $row;
                $type = 'general';

                // Rules (same as before)
                if (in_array($id, ['D3', 'E5', 'B7', 'I7'])) {
                    $type = 'broken';
                } elseif (in_array($id, ['A2', 'A10', 'I10'])) {
                    $type = 'accessible';
                } elseif (in_array($id, ['I2', 'I3', 'I4'])) {
                    $type = 'vip';
                } elseif (in_array($row, [1])) {
                    $type = 'no-children';
                }

                $this->seats[$id] = new Seat($id, $row, $col, $type, $this->db);
            }
        }
    }

    private function loadBookings() {
        if ($this->db) {
            $query = "SELECT * FROM bookings";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($this->seats[$row['seat_id']])) {
                    $this->seats[$row['seat_id']]->isBooked = true;
                    $this->seats[$row['seat_id']]->passenger = $row['passenger_name'];
                }
            }
        }
    }

    private function isSeatSuitable($seat, $passenger, $phase) {
    // Universal checks
    if (!$seat->isAvailable() || $seat->type === 'broken') {
        return false;
    }
    
    // VIP checks
    if ($passenger->isVIP && $seat->type !== 'vip') return false;
    if (!$passenger->isVIP && $seat->type === 'vip') return false;
    
    // Accessible checks
    if ($passenger->needsAccessibleSeat && $seat->type !== 'accessible') return false;
    if (!$passenger->needsAccessibleSeat && $seat->type === 'accessible') return false;
    
    // Child checks
    if ($passenger->isChild && $seat->type === 'no-children') return false;
    
    // Solo traveler logic
    if ($passenger->bookingType === 'solo') {
        $col = $seat->column;
        
        // Phase 1: Window seats (A, I) - including no-children if adult
        if ($phase === 1) {
            return in_array($col, ['A', 'I']);
        }
        
        // Phase 2: Aisle seats (C, D, F, G) - including no-children if adult
        if ($phase === 2) {
            return in_array($col, ['C', 'D', 'F', 'G']);
        }
        
        // Phase 3: Middle seats (B, E, H) - only for adults who selected "no children"
        if ($phase === 3 && !$passenger->isChild) {
            return in_array($col, ['B', 'E', 'H']);
        }
        
        return false;
    }
    
    return true;
}

public function findAvailableSeat($passenger) {
    // For solo travelers
    if ($passenger->bookingType === 'solo') {
        // Try phases in order
        for ($phase = 1; $phase <= 3; $phase++) {
            foreach ($this->seats as $seat) {
                if ($this->isSeatSuitable($seat, $passenger, $phase)) {
                    return $seat;
                }
            }
            
            // Skip phase 3 (middle seats) for children
            if ($phase === 2 && $passenger->isChild) {
                break;
            }
        }
        return null;
    }
    
    // For groups/non-solo
    foreach ($this->seats as $seat) {
        if ($this->isSeatSuitable($seat, $passenger, 0)) { // Phase 0 = no special handling
            return $seat;
        }
    }
    return null;
}

    public function assignSeat($seat, $passenger) {
        // Check if passenger already has a seat assigned
        if ($this->isPassengerAlreadyAssigned($passenger->name)) {
            return [
                'success' => false,
                'message' => 'Passenger already has a seat assigned'
            ];
        }

        $result = $seat->assignPassenger($passenger);
        
        // If group booking, assign group members
        if ($result['success'] && $passenger->bookingType === 'group') {
            $this->assignGroupMembers($passenger);
        }
        
        return $result;
    }

    private function isPassengerAlreadyAssigned($passengerName) {
        if ($this->db) {
            $query = "SELECT COUNT(*) FROM bookings WHERE passenger_name = :passenger_name";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':passenger_name', $passengerName);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        }
        return false;
    }

    public function findAdjacentSeats($passenger, $groupSize) {
            // Define seat columns and types
            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
            $middleColumns = ['B','C', 'D', 'E', 'F', 'G', 'H']; // Preferred group seating area
            
            // First try to find adjacent seats in middle columns
            foreach (range(1, 10) as $row) {
                $potentialGroup = [];
                
                foreach ($middleColumns as $col) {
                    $seatId = $col . $row;
                    if (isset($this->seats[$seatId])) {
                        $seat = $this->seats[$seatId];
                        if ($this->isGroupSeatSuitable($seat, $passenger)) {
                            $potentialGroup[] = $seat;
                            
                            // Check if we have enough adjacent seats
                            if (count($potentialGroup) >= $groupSize) {
                                return array_slice($potentialGroup, 0, $groupSize);
                            }
                        } else {
                            $potentialGroup = []; // Reset if sequence breaks
                        }
                    }
                }
            }
            
            // If no middle seats, try any adjacent seats
            foreach (range(1, 10) as $row) {
                $potentialGroup = [];
                
                foreach ($columns as $col) {
                    $seatId = $col . $row;
                    if (isset($this->seats[$seatId])) {
                        $seat = $this->seats[$seatId];
                        if ($this->isGroupSeatSuitable($seat, $passenger)) {
                            $potentialGroup[] = $seat;
                            
                            if (count($potentialGroup) >= $groupSize) {
                                return array_slice($potentialGroup, 0, $groupSize);
                            }
                        } else {
                            $potentialGroup = []; // Reset if sequence breaks
                        }
                    }
                }
            }
            
            return null; // No suitable adjacent seats found
        }

        private function isGroupSeatSuitable($seat, $passenger) {
            // Universal checks
            if (!$seat->isAvailable() || $seat->type === 'broken') {
                return false;
            }
            
            // VIP checks
            if ($passenger->isVIP && $seat->type !== 'vip') return false;
            if (!$passenger->isVIP && $seat->type === 'vip') return false;
            
            // Accessible checks
            if ($passenger->needsAccessibleSeat && $seat->type !== 'accessible') return false;
            if (!$passenger->needsAccessibleSeat && $seat->type === 'accessible') return false;
            
            // Child checks
            if ($passenger->isChild && $seat->type === 'no-children') return false;
            
            // Group-specific rules
            if ($seat->type === 'no-children' && $passenger->isChild) return false;
            
            return true;
    }

    public function assignGroupSeats($passenger, $groupSize) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'No database connection'];
        }

        try {
            $this->db->beginTransaction();

            // Check if group leader already has a booking
            $checkStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM bookings WHERE passenger_name = ? AND booking_type = 'group'"
            );
            $checkStmt->execute([$passenger->name]);
            if ($checkStmt->fetchColumn() > 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This passenger is already part of a group'];
            }

            // Find adjacent seats
            $seats = $this->findAdjacentSeats($passenger, $groupSize);
            if (!$seats || count($seats) < $groupSize) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Not enough adjacent seats available'];
            }

            // Check if any seats are already booked
            foreach ($seats as $seat) {
                if (!$seat->isAvailable()) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Seat ' . $seat->id . ' is no longer available'];
                }
            }

            // Create group record
            $groupStmt = $this->db->prepare(
                "INSERT INTO groups (leader_name, size) VALUES (?, ?)"
            );
            $groupStmt->execute([$passenger->name, $groupSize]);
            $groupId = $this->db->lastInsertId();

            // Assign all seats
            $assignedSeats = [];
            $assignStmt = $this->db->prepare(
                "INSERT INTO bookings 
                (seat_id, passenger_name, is_vip, needs_accessible, is_child, booking_type, group_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($seats as $seat) {
                $assignStmt->execute([
                    $seat->id,
                    $passenger->name,
                    $passenger->isVIP,
                    $passenger->needsAccessibleSeat,
                    $passenger->isChild,
                    'group',
                    $groupId
                ]);
                
                $seat->isBooked = true;
                $seat->passenger = $passenger->name;
                $assignedSeats[] = $seat->id;
            }

            $this->db->commit();
            return [
                'success' => true,
                'message' => 'Group of ' . $groupSize . ' assigned successfully',
                'seatIds' => $assignedSeats,
                'groupId' => $groupId
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function isPassengerBooked($passengerName, $bookingType = null) {
        if (!$this->db) {
            return ['booked' => false, 'group_id' => null];
        }

        $query = "SELECT group_id FROM bookings WHERE passenger_name = ?";
        $params = [$passengerName];
        
        if ($bookingType) {
            $query .= " AND booking_type = ?";
            $params[] = $bookingType;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'booked' => $result !== false,
            'group_id' => $result['group_id'] ?? null
        ];
    }

    public function cancelBooking($seatNumber) {
        // Check if seat exists
        if (!isset($this->seats[$seatNumber])) {
            return false;
        }

        // Get the Seat object
        $seat = $this->seats[$seatNumber];

        // Check if seat is actually booked
        if ($seat->getPassenger() === null) {
            return false; // seat wasn't booked
        }

        // Free up the seat
        $seat->setPassenger(null);
        $seat->setStatus('available');

        return true; // cancellation successful
    }
}