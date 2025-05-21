<?php
class Passenger {
    public $name;
    public $isVIP;
    public $needsAccessible;
    public $isChild;
    public $bookingType;
    public $groupSize;

    public function __construct($name, $isVIP = false, $needsAccessible = false, 
                              $isChild = false, $bookingType = 'solo', $groupSize = 1) {
        $this->name = $name;
        $this->isVIP = $isVIP;
        $this->needsAccessibleSeat = $needsAccessible;
        $this->isChild = $isChild;
        $this->bookingType = $bookingType;
        $this->groupSize = $groupSize;
    }
}