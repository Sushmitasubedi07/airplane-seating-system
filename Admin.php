<?php
require_once __DIR__ . '/Passenger.php'; // Add this at the top
require_once __DIR__ . '/SeatMap.php';
class Admin {
    private $password = 'admin'; // Changed default password to 'admin'
    private $isAuthenticated = false;
    
    public function verifyPassword($inputPassword) {
        $this->isAuthenticated = ($inputPassword === $this->password);
        return $this->isAuthenticated;
    }
    
    public function isAuthenticated() {
        return $this->isAuthenticated;
    }
    
    public function logout() {
        $this->isAuthenticated = false;
    }
    
    
    public function overrideSeatAssignment($seatMap, $seatId, $newPassengerName) {
        if (session_status() === PHP_SESSION_NONE || empty($_SESSION['admin_authenticated'])) {
            throw new Exception('Admin session invalid');
        }

        try {
            $seat = $seatMap->seats[$seatId] ?? null;
            if (!$seat) {
                return ['success' => false, 'message' => 'Seat not found'];
            }
            
            // Remove any existing booking
            if ($seat->isBooked) {
                $seatMap->cancelBooking($seatId);
            }
            
            // Create temporary passenger for admin override
            $passenger = new Passenger(
                $newPassengerName,
                false,  // isVIP
                false,  // needsAccessible
                false,  // isChild
                'admin' // bookingType
            );
            
            // Assign the seat
            $result = $seatMap->assignSeat($seat, $passenger);
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => "Admin override: Seat {$seatId} assigned to {$newPassengerName}"
                ];
            }
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Override failed: ' . $e->getMessage()];
        }
    }
}