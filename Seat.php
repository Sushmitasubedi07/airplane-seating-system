<?php
class Seat {
    public $id;
    public $row;
    public $column;
    public $type; // general, vip, accessible, broken, no-children
    public $isBooked = false;
    public $passenger = null;
    private $db;

    public function __construct($id, $row, $column, $type, $db = null) {
        $this->id = $id;
        $this->row = $row;
        $this->column = $column;
        $this->type = $type;
        $this->db = $db;
    }

    public function assignPassenger($passenger) {
        if ($this->type === 'broken') {
            return [
                'success' => false,
                'message' => 'Cannot assign to a broken seat'
            ];
        }

        if ($this->isBooked) {
            return [
                'success' => false,
                'message' => 'Seat already booked'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Check if passenger already has a seat
            $checkQuery = "SELECT COUNT(*) FROM bookings WHERE passenger_name = :passenger_name";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':passenger_name', $passenger->name);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Passenger already has a seat assigned'
                ];
            }

            // Assign the seat
            $query = "INSERT INTO bookings 
                     (seat_id, passenger_name, is_vip, needs_accessible, is_child, booking_type, booking_time)
                     VALUES
                     (:seat_id, :passenger_name, :is_vip, :needs_accessible, :is_child, :booking_type, NOW())";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':seat_id', $this->id);
            $stmt->bindParam(':passenger_name', $passenger->name);
            $stmt->bindParam(':is_vip', $passenger->isVIP, PDO::PARAM_BOOL);
            $stmt->bindParam(':needs_accessible', $passenger->needsAccessibleSeat, PDO::PARAM_BOOL);
            $stmt->bindParam(':is_child', $passenger->isChild, PDO::PARAM_BOOL);
            $stmt->bindParam(':booking_type', $passenger->bookingType);
            $stmt->execute();

            $this->isBooked = true;
            $this->passenger = $passenger;

            $this->db->commit();
            return [
                'success' => true,
                'message' => 'Seat assigned successfully'
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Assignment failed: ' . $e->getMessage()
            ];
        }
    }

    public function isAvailable() {
        if ($this->db) {
            $query = "SELECT COUNT(*) FROM bookings WHERE seat_id = :seat_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':seat_id', $this->id);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            $this->isBooked = ($count > 0);
        }
        return !$this->isBooked && $this->type !== 'broken';
    }

    public function getPassenger() {
        return $this->passenger;
    }
    
    public function setPassenger($passenger) {
        $this->passenger = $passenger;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function setStatus($status) {
        $this->status = $status;
    }
}