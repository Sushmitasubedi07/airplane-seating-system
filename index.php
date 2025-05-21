<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Seat.php';
require_once __DIR__ . '/classes/Passenger.php';
require_once __DIR__ . '/classes/SeatMap.php';

$database = new Database();
$db = $database->getConnection();

$seatMap = new SeatMap();
$allocatedSeat = null;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingType = $_POST['booking_type'];
    $isVIP = isset($_POST['isVIP']);
    $needsAccessible = isset($_POST['isAccessible']);
    $isChild = isset($_POST['noChildren']) ? false : true;
    $groupSize = ($bookingType === 'group') ? (int)$_POST['group_size'] : 1;
    
    // Get passenger name based on booking type
    $passengerName = ($bookingType === 'solo') 
        ? $_POST['solo_passenger'] 
        : $_POST['group_leader'];

    // Create passenger with group size
    $passenger = new Passenger($passengerName, $isVIP, $needsAccessible, $isChild, $bookingType, $groupSize);

    if ($bookingType === 'group') {
        $groupSize = (int)$_POST['group_size'];
        $passenger = new Passenger(
            $_POST['group_leader'],
            $isVIP,
            $needsAccessible,
            $isChild,
            'group',
            $groupSize
        );

        // First check if group leader already has a booking
        $check = $seatMap->isPassengerBooked($passenger->name, 'group');
        if ($check['booked']) {
            $message = "<div class='error'>This group leader already has a booking (Group ID: {$check['group_id']}). 
                       Please select a different leader.</div>";
        } else {
            $result = $seatMap->assignGroupSeats($passenger, $groupSize);
            
            if ($result['success']) {
                $message = "<div class='success'>Group of {$groupSize} assigned to seats: " . 
                          implode(', ', $result['seatIds']) . "</div>";
            } else {
                $message = "<div class='error'>" . $result['message'] . "</div>";
            }
        }
    } else {
        // Handle solo booking
        $allocatedSeat = $seatMap->findAvailableSeat($passenger);
        
        if ($allocatedSeat) {
            $result = $seatMap->assignSeat($allocatedSeat, $passenger);
            
            if ($result['success']) {
                $message = "<div class='success'>Seat {$allocatedSeat->id} assigned to {$passengerName}</div>";
            } else {
                $message = "<div class='error'>" . $result['message'] . "</div>";
            }
        } else {
            $message = "<div class='override'>No suitable seat available. Admin override required.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Plan-driven software development mode</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Airplane Seating System</h1>
    <div class="main-container">
    <div class="left-panel">
            <h1>Airplane Seat Allocation</h1>
            <?= $message ?>
           <form method="POST">
                <label>
                    Passenger Type:
                    <select name="booking_type" id="booking-type" required>
                        <option value="solo">Solo</option>
                        <option value="group">Group</option>
                    </select>
                </label>

                <div id="solo-passenger-field">
                    <label>
                        Select Passenger (Solo):
                        <select name="solo_passenger" required>
                            <option value="passenger1">Passenger 1</option>
                            <option value="passenger2">Passenger 2</option>
                            <option value="passenger3">Passenger 3</option>
                            <option value="passenger4">Passenger 4</option>
                            <option value="passenger5">Passenger 5</option>
                            <option value="passenger6">Passenger 6</option>
                            <option value="passenger7">Passenger 7</option>
                            <option value="passenger8">Passenger 8</option>
                            <option value="passenger9">Passenger 9</option>
                            <option value="passenger10">Passenger 10</option>
                            <option value="passenger11">Passenger 11</option>
                            <option value="passenger12">Passenger 12</option>
                            <option value="passenger13">Passenger 13</option>
                            <option value="passenger14">Passenger 14</option>
                            <option value="passenger15">Passenger 15</option>
                            <option value="passenger16">Passenger 16</option>
                            <option value="passenger17">Passenger 17</option>
                            <option value="passenger18">Passenger 18</option>
                            <option value="passenger19">Passenger 19</option>
                            <option value="passenger20">Passenger 20</option>
                        </select>
                    </label>
                </div>

                <div id="group-passenger-field" style="display:none;">
                    <label>
                        Select Group Leader:
                        <select name="group_leader" required>
                            <option value="group1">Group Leader 1</option>
                            <option value="group2">Group Leader 2</option>
                            <option value="group3">Group Leader 3</option>
                            <option value="group4">Group Leader 4</option>
                            <option value="group5">Group Leader 5</option>
                            <option value="group6">Group Leader 6</option>
                            <option value="group7">Group Leader 7</option>
                            <option value="group8">Group Leader 8</option>
                            <option value="group9">Group Leader 9</option>
                            <option value="group10">Group Leader 10</option>
                            <option value="group11">Group Leader 11</option>
                            <option value="group12">Group Leader 12</option>
                            <option value="group13">Group Leader 13</option>
                            <option value="group14">Group Leader 14</option>
                            <option value="group15">Group Leader 15</option>
                            <option value="group16">Group Leader 16</option>
                            <option value="group17">Group Leader 17</option>
                            <option value="group18">Group Leader 18</option>
                            <option value="group19">Group Leader 19</option>
                            <option value="group20">Group Leader 20</option>
                        </select>
                    </label>

                    <div id="group-size-field" style="margin-top: 5px;">
                        <label>
                            Group Size (2–7) <span style="color:red">*</span>:
                            <select name="group_size" id="group-size">
                                <option value="">Select group size</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                            </select>
                        </label>
                    </div>
                </div>

                <label>
                    VIP Passenger:
                    <input type="checkbox" name="isVIP" value="1"/>
                </label>
                
                <label>
                    Needs Accessible Seat:
                    <input type="checkbox" name="isAccessible" value="1"/>
                </label>

                <label>
                    No Children: 
                    <input type="checkbox" name="noChildren" value="1"/>
                </label>

                <input type="submit" value="Allocate Seat(s)" />
            </form>
        </div>
        <div class="right-panel">
            <div class="container">
                <div class="seating-plan">
                    <div class="header-row">
                        <div class="header-cell"></div>
                        <div class="header-cell col-a">A</div>
                        <div class="header-cell col-b">B</div>
                        <div class="header-cell col-c">C</div>
                        <div class="header-cell col-d">D</div>
                        <div class="header-cell col-e">E</div>
                        <div class="header-cell col-f">F</div>
                        <div class="header-cell col-g">G</div>
                        <div class="header-cell col-h">H</div>
                        <div class="header-cell col-i">I</div>
                    </div>
                    
                    
                    <?php 
                    // Get the seat map instance (should be created earlier in your code)
                    $seatMap = new SeatMap();

                    for ($row = 1; $row <= 10; $row++): ?>
                        <div class="row">
                            <div class="row-number"><?= $row ?></div>
                            <?php 
                            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
                            foreach ($columns as $col): 
                                $seatId = $col . $row;
                                $seat = $seatMap->seats[$seatId] ?? null;
                                
                                if ($seat): ?>
                                    <div class="seat col-<?= strtolower($col) ?> 
                                        <?= $seat->isBooked ? 'booked' : '' ?> 
                                        seat-type-<?= $seat->type ?>"
                                        data-id="<?= $seat->id ?>"
                                        data-type="<?= $seat->isBooked ? 'booked' : $seat->type ?>"
                                        data-booked="<?= $seat->isBooked ? 'true' : 'false' ?>">
                                        <?= $seat->id ?>
                                    </div>
                                <?php endif; 
                            endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="legend">
                    <div class="total-seats">Total (90 Seats)</div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #65CC60;"></div>
                        <span>General</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #1F5BF5;"></div>
                        <span>VIP Zone</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #FAFF5C;"></div>
                        <span>Accessible Seat</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #838181;"></div>
                        <span>Broken Seat</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #FFCFF9;"></div>
                        <span>Age Restricted</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #F92929;"></div>
                        <span>Booked</span>
                    </div>
                </div>
            </div>

            <!-- Add this near your admin-override div -->
            <div class="admin-controls-container">
                <div class="admin-controls">
                    <button id="adminOverrideBtn" class="admin-override">Admin Override</button>
                    <button id="resetSeatsBtn" class="reset-btn">Reset All Seats</button>
                </div>
            </div>
            <div id="adminPopup" class="popup-overlay">
                <div class="popup-content">
                    <h3>Admin Authentication</h3>
                    <form id="adminPasswordForm">
                        <div class="form-group">
                            <label for="adminPassword">Enter Admin Password:admin</label>
                            <input type="password" id="adminPassword" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Login</button>
                            <button type="button" id="cancelAdminBtn" class="btn-cancel">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <button id="logoutAdminBtn" style="display:none !important;" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout Admin
            </button>
            <div class="seat-details">
                <div class="seat-details-section">
                    <h2 class="vip-title">Preference VIP Seats</h2>
                    <table class="seat-table">
                        <tr>
                            <th>Rows</th>
                            <th>Seats</th>
                        </tr>
                        <tr>
                            <td>Rows 1, 2, 3</td>
                            <td>I2, I3, I4</td>
                        </tr>
                    </table>

                </div>

                <div class="seat-details-section">
                    <h2 class="accessible-title" style="color:#464545 !important">Preference Accessible Seats</h2>
                    <table class="seat-table">
                        <tr>
                            <th>Rows</th>
                            <th>Seats</th>
                        </tr>
                        <tr>
                            <td>Rows 1, 10</td>
                            <td>A2, A10, I10</td>
                        </tr>
                    </table>
                    
                </div>

                <div class="seat-details-section">
                    <h2 class="broken-title">Preference Broken Seats</h2>
                    <table class="seat-table">
                        <tr>
                            <th>Rows</th>
                            <th>Seats</th>
                        </tr>
                        <tr>
                            <td>Rows 3, 5, 7</td>
                            <td>D3, E5, B7, I7</td>
                        </tr>
                    </table>
                </div>
                <div class="seat-details-section">
                    <h2 class="childrestricted-title" style="color:#464545 !important">Preference Child Restricted</h2>
                    <table class="seat-table">
                        <tr>
                            <th>Rows</th>
                            <th>Seats</th>
                        </tr>
                        <tr>
                            <td>Rows 1</td>
                            <td>A1, B1, C1, D1, E1, F1, G1, H1, I1</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="seat-details second">
                <div class="seat-details-section">
                    <h2 class="general-title">Preference General Seats</h2>
                    <table class="seat-table">
                        <tr>
                            <th>Rows</th>
                            <th>Seats</th>
                        </tr>
                        <tr>
                            <td>Rows 1-10</td>
                            <td>B2, C2, D2, E2, F2, G2, H2, A3, B3, C3, E3, F3, G3, H3, A4, B4, C4, D4, E4, F4, G4, H4, A5, B5, C5, D5, F5, G5, H5, I5, A6, B6, C6, D6, E6, F6, G6, H6, I6, A7, C7, D7, E7, F7, G7, H7, A8, B8, C8, D8, E8, F8, G8, H8, I8, A9, B9, C9, D9, E9, F9, G9, H9, I9, B10, C10, D10, E10, F10, G10, H10</td>
                        </tr>
                    </table>
                </div>
                
            </div>
        </div>
    </div>
<script type="text/javascript" src="js/main.js"></script>
</body>
</html>
