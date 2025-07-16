<?php
session_start();
require_once '../config/config.php';

// Check authentication
$auth = Auth::getInstance();
$auth->requireLogin();

// Check permissions
$auth->requirePermission('sync_ota');

// Get current user
$user = $auth->getCurrentUser();

// Check rate limiting
if (!rateLimitCheck(getClientIP(), 10, 60)) {
    jsonResponse(['error' => 'Rate limit exceeded'], 429);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['error' => 'Invalid JSON data'], 400);
}

$action = $input['action'] ?? '';
$channel = $input['channel'] ?? '';

try {
    $otaManager = new OTAManager();
    $hotelId = $user['hotel_id'] ?? 1;
    
    switch ($action) {
        case 'sync_rates':
            $roomTypeId = $input['room_type_id'] ?? null;
            $startDate = $input['start_date'] ?? date('Y-m-d');
            $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
            
            if (!$roomTypeId) {
                jsonResponse(['error' => 'Room type ID is required'], 400);
            }
            
            $result = $otaManager->syncRates($hotelId, $roomTypeId, $startDate, $endDate, $channel);
            
            jsonResponse([
                'success' => true,
                'message' => 'Rates synchronized successfully',
                'data' => $result
            ]);
            break;
            
        case 'sync_availability':
            $roomTypeId = $input['room_type_id'] ?? null;
            $startDate = $input['start_date'] ?? date('Y-m-d');
            $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
            
            if (!$roomTypeId) {
                jsonResponse(['error' => 'Room type ID is required'], 400);
            }
            
            $result = $otaManager->syncAvailability($hotelId, $roomTypeId, $startDate, $endDate, $channel);
            
            jsonResponse([
                'success' => true,
                'message' => 'Availability synchronized successfully',
                'data' => $result
            ]);
            break;
            
        case 'sync_restrictions':
            $roomTypeId = $input['room_type_id'] ?? null;
            $startDate = $input['start_date'] ?? date('Y-m-d');
            $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
            
            if (!$roomTypeId) {
                jsonResponse(['error' => 'Room type ID is required'], 400);
            }
            
            $result = $otaManager->syncRestrictions($hotelId, $roomTypeId, $startDate, $endDate, $channel);
            
            jsonResponse([
                'success' => true,
                'message' => 'Restrictions synchronized successfully',
                'data' => $result
            ]);
            break;
            
        case 'fetch_reservations':
            $startDate = $input['start_date'] ?? null;
            $endDate = $input['end_date'] ?? null;
            
            $result = $otaManager->fetchReservations($hotelId, $channel, $startDate, $endDate);
            
            jsonResponse([
                'success' => true,
                'message' => 'Reservations fetched successfully',
                'data' => $result
            ]);
            break;
            
        case 'sync_all':
            $results = [];
            
            // Get all room types for the hotel
            $db = Database::getInstance();
            $roomTypes = $db->select('room_types', 'id', ['hotel_id' => $hotelId]);
            
            foreach ($roomTypes as $roomType) {
                $roomTypeId = $roomType['id'];
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime('+30 days'));
                
                // Sync rates
                $rateResult = $otaManager->syncRates($hotelId, $roomTypeId, $startDate, $endDate, $channel);
                $results['rates'][$roomTypeId] = $rateResult;
                
                // Sync availability
                $availResult = $otaManager->syncAvailability($hotelId, $roomTypeId, $startDate, $endDate, $channel);
                $results['availability'][$roomTypeId] = $availResult;
                
                // Sync restrictions
                $restrictResult = $otaManager->syncRestrictions($hotelId, $roomTypeId, $startDate, $endDate, $channel);
                $results['restrictions'][$roomTypeId] = $restrictResult;
            }
            
            // Fetch reservations
            $reservationResult = $otaManager->fetchReservations($hotelId, $channel);
            $results['reservations'] = $reservationResult;
            
            jsonResponse([
                'success' => true,
                'message' => 'Full synchronization completed',
                'data' => $results
            ]);
            break;
            
        case 'channel_status':
            $status = $otaManager->getChannelStatus($hotelId);
            
            jsonResponse([
                'success' => true,
                'data' => $status
            ]);
            break;
            
        case 'sync_logs':
            $limit = $input['limit'] ?? 50;
            $logs = $otaManager->getSyncLogs($hotelId, $limit);
            
            jsonResponse([
                'success' => true,
                'data' => $logs
            ]);
            break;
            
        default:
            // Default sync action for specific channel
            if ($channel) {
                $results = [];
                
                // Get all room types for the hotel
                $db = Database::getInstance();
                $roomTypes = $db->select('room_types', 'id', ['hotel_id' => $hotelId]);
                
                foreach ($roomTypes as $roomType) {
                    $roomTypeId = $roomType['id'];
                    $startDate = date('Y-m-d');
                    $endDate = date('Y-m-d', strtotime('+30 days'));
                    
                    // Get OTA channel ID from name
                    $channelData = $db->selectOne('ota_channels', 'id', ['name' => $channel]);
                    $channelId = $channelData ? $channelData['id'] : null;
                    
                    // Sync rates
                    $rateResult = $otaManager->syncRates($hotelId, $roomTypeId, $startDate, $endDate, $channelId);
                    $results['rates'][$roomTypeId] = $rateResult;
                    
                    // Sync availability
                    $availResult = $otaManager->syncAvailability($hotelId, $roomTypeId, $startDate, $endDate, $channelId);
                    $results['availability'][$roomTypeId] = $availResult;
                    
                    // Sync restrictions
                    $restrictResult = $otaManager->syncRestrictions($hotelId, $roomTypeId, $startDate, $endDate, $channelId);
                    $results['restrictions'][$roomTypeId] = $restrictResult;
                }
                
                // Fetch reservations
                $reservationResult = $otaManager->fetchReservations($hotelId, $channelId);
                $results['reservations'] = $reservationResult;
                
                jsonResponse([
                    'success' => true,
                    'message' => "Synchronization with {$channel} completed",
                    'data' => $results
                ]);
            } else {
                jsonResponse(['error' => 'Invalid action or missing channel'], 400);
            }
            break;
    }
    
} catch (Exception $e) {
    Logger::error('Sync API error: ' . $e->getMessage());
    jsonResponse(['error' => 'Synchronization failed: ' . $e->getMessage()], 500);
}
?>