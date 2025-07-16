<?php
class OTAManager {
    private $db;
    private $otaAdapters = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeAdapters();
    }
    
    private function initializeAdapters() {
        $this->otaAdapters = [
            'booking' => new BookingAdapter(),
            'agoda' => new AgodaAdapter(),
            'expedia' => new ExpediaAdapter()
        ];
    }
    
    public function syncRates($hotelId, $roomTypeId, $startDate, $endDate, $otaChannelId = null) {
        try {
            $channels = $this->getActiveChannels($hotelId, $otaChannelId);
            $results = [];
            
            foreach ($channels as $channel) {
                $result = $this->syncRatesForChannel($hotelId, $roomTypeId, $startDate, $endDate, $channel);
                $results[$channel['name']] = $result;
            }
            
            return $results;
        } catch (Exception $e) {
            Logger::error("Rate sync failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function syncRatesForChannel($hotelId, $roomTypeId, $startDate, $endDate, $channel) {
        $adapter = $this->getAdapter($channel['name']);
        
        if (!$adapter) {
            throw new Exception("No adapter found for OTA: " . $channel['name']);
        }
        
        // Get inventory data
        $inventory = $this->getInventoryData($roomTypeId, $startDate, $endDate);
        
        // Format data for OTA
        $formattedData = $adapter->formatRateData($inventory);
        
        // Send to OTA
        $response = $adapter->updateRates($channel, $formattedData);
        
        // Log the sync
        $this->logSync($hotelId, $channel['id'], 'rates', 
                      $response['success'] ? 'success' : 'error',
                      $response['message'], $formattedData);
        
        return $response;
    }
    
    public function syncAvailability($hotelId, $roomTypeId, $startDate, $endDate, $otaChannelId = null) {
        try {
            $channels = $this->getActiveChannels($hotelId, $otaChannelId);
            $results = [];
            
            foreach ($channels as $channel) {
                $result = $this->syncAvailabilityForChannel($hotelId, $roomTypeId, $startDate, $endDate, $channel);
                $results[$channel['name']] = $result;
            }
            
            return $results;
        } catch (Exception $e) {
            Logger::error("Availability sync failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function syncAvailabilityForChannel($hotelId, $roomTypeId, $startDate, $endDate, $channel) {
        $adapter = $this->getAdapter($channel['name']);
        
        if (!$adapter) {
            throw new Exception("No adapter found for OTA: " . $channel['name']);
        }
        
        // Get inventory data
        $inventory = $this->getInventoryData($roomTypeId, $startDate, $endDate);
        
        // Format data for OTA
        $formattedData = $adapter->formatAvailabilityData($inventory);
        
        // Send to OTA
        $response = $adapter->updateAvailability($channel, $formattedData);
        
        // Log the sync
        $this->logSync($hotelId, $channel['id'], 'inventory', 
                      $response['success'] ? 'success' : 'error',
                      $response['message'], $formattedData);
        
        return $response;
    }
    
    public function syncRestrictions($hotelId, $roomTypeId, $startDate, $endDate, $otaChannelId = null) {
        try {
            $channels = $this->getActiveChannels($hotelId, $otaChannelId);
            $results = [];
            
            foreach ($channels as $channel) {
                $result = $this->syncRestrictionsForChannel($hotelId, $roomTypeId, $startDate, $endDate, $channel);
                $results[$channel['name']] = $result;
            }
            
            return $results;
        } catch (Exception $e) {
            Logger::error("Restrictions sync failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function syncRestrictionsForChannel($hotelId, $roomTypeId, $startDate, $endDate, $channel) {
        $adapter = $this->getAdapter($channel['name']);
        
        if (!$adapter) {
            throw new Exception("No adapter found for OTA: " . $channel['name']);
        }
        
        // Get inventory data
        $inventory = $this->getInventoryData($roomTypeId, $startDate, $endDate);
        
        // Format data for OTA
        $formattedData = $adapter->formatRestrictionsData($inventory);
        
        // Send to OTA
        $response = $adapter->updateRestrictions($channel, $formattedData);
        
        // Log the sync
        $this->logSync($hotelId, $channel['id'], 'restrictions', 
                      $response['success'] ? 'success' : 'error',
                      $response['message'], $formattedData);
        
        return $response;
    }
    
    public function fetchReservations($hotelId, $otaChannelId = null, $startDate = null, $endDate = null) {
        try {
            $channels = $this->getActiveChannels($hotelId, $otaChannelId);
            $allReservations = [];
            
            foreach ($channels as $channel) {
                $reservations = $this->fetchReservationsForChannel($hotelId, $channel, $startDate, $endDate);
                $allReservations = array_merge($allReservations, $reservations);
            }
            
            return $allReservations;
        } catch (Exception $e) {
            Logger::error("Reservation fetch failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function fetchReservationsForChannel($hotelId, $channel, $startDate, $endDate) {
        $adapter = $this->getAdapter($channel['name']);
        
        if (!$adapter) {
            throw new Exception("No adapter found for OTA: " . $channel['name']);
        }
        
        // Fetch reservations from OTA
        $response = $adapter->fetchReservations($channel, $startDate, $endDate);
        
        if ($response['success']) {
            // Process and store reservations
            $reservations = $this->processReservations($hotelId, $channel['id'], $response['data']);
            
            // Log the sync
            $this->logSync($hotelId, $channel['id'], 'reservations', 'success',
                          'Fetched ' . count($reservations) . ' reservations');
            
            return $reservations;
        } else {
            $this->logSync($hotelId, $channel['id'], 'reservations', 'error',
                          $response['message']);
            return [];
        }
    }
    
    private function processReservations($hotelId, $otaChannelId, $reservationsData) {
        $processed = [];
        
        foreach ($reservationsData as $reservationData) {
            // Check if reservation already exists
            $existing = $this->db->selectOne('reservations', '*', [
                'ota_channel_id' => $otaChannelId,
                'ota_reservation_id' => $reservationData['id']
            ]);
            
            if ($existing) {
                // Update existing reservation
                $this->updateReservation($existing['id'], $reservationData);
                $processed[] = $existing['id'];
            } else {
                // Create new reservation
                $reservationId = $this->createReservation($hotelId, $otaChannelId, $reservationData);
                $processed[] = $reservationId;
            }
        }
        
        return $processed;
    }
    
    private function createReservation($hotelId, $otaChannelId, $data) {
        // Find room type ID from OTA room type
        $roomTypeId = $this->findRoomTypeId($hotelId, $data['room_type']);
        
        $reservationData = [
            'hotel_id' => $hotelId,
            'ota_channel_id' => $otaChannelId,
            'ota_reservation_id' => $data['id'],
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'] ?? '',
            'guest_phone' => $data['guest_phone'] ?? '',
            'room_type_id' => $roomTypeId,
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'adults' => $data['adults'] ?? 1,
            'children' => $data['children'] ?? 0,
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'confirmed',
            'special_requests' => $data['special_requests'] ?? ''
        ];
        
        return $this->db->insert('reservations', $reservationData);
    }
    
    private function updateReservation($reservationId, $data) {
        $updateData = [
            'status' => $data['status'] ?? 'confirmed',
            'special_requests' => $data['special_requests'] ?? ''
        ];
        
        if ($data['status'] === 'cancelled') {
            $updateData['cancellation_date'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('reservations', $updateData, ['id' => $reservationId]);
    }
    
    private function findRoomTypeId($hotelId, $otaRoomType) {
        // This is a simplified mapping - in real implementation, you'd have a proper mapping table
        $roomType = $this->db->selectOne('room_types', 'id', [
            'hotel_id' => $hotelId,
            'name' => $otaRoomType
        ]);
        
        return $roomType ? $roomType['id'] : null;
    }
    
    private function getActiveChannels($hotelId, $otaChannelId = null) {
        $conditions = ['is_active' => 1];
        
        if ($otaChannelId) {
            $conditions['id'] = $otaChannelId;
        }
        
        $channels = $this->db->select('ota_channels', '*', $conditions);
        
        // Filter by hotel mappings
        $activeChannels = [];
        foreach ($channels as $channel) {
            $mapping = $this->db->selectOne('hotel_ota_mappings', '*', [
                'hotel_id' => $hotelId,
                'ota_channel_id' => $channel['id'],
                'is_active' => 1
            ]);
            
            if ($mapping) {
                $channel['mapping'] = $mapping;
                $activeChannels[] = $channel;
            }
        }
        
        return $activeChannels;
    }
    
    private function getInventoryData($roomTypeId, $startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT * FROM room_inventory 
            WHERE room_type_id = ? 
            AND date BETWEEN ? AND ?
            ORDER BY date
        ", [$roomTypeId, $startDate, $endDate]);
    }
    
    private function getAdapter($otaName) {
        $adapterKey = strtolower($otaName);
        return $this->otaAdapters[$adapterKey] ?? null;
    }
    
    private function logSync($hotelId, $otaChannelId, $syncType, $status, $message, $data = []) {
        $logData = [
            'hotel_id' => $hotelId,
            'ota_channel_id' => $otaChannelId,
            'sync_type' => $syncType,
            'status' => $status,
            'message' => $message,
            'request_data' => json_encode($data),
            'response_data' => json_encode([])
        ];
        
        $this->db->insert('sync_logs', $logData);
        
        // Also log to application log
        Logger::logOTASync($otaName ?? 'Unknown', $syncType, $status, $message, $data);
    }
    
    public function getSyncLogs($hotelId, $limit = 100) {
        return $this->db->fetchAll("
            SELECT sl.*, oc.name as ota_name 
            FROM sync_logs sl
            JOIN ota_channels oc ON sl.ota_channel_id = oc.id
            WHERE sl.hotel_id = ?
            ORDER BY sl.created_at DESC
            LIMIT ?
        ", [$hotelId, $limit]);
    }
    
    public function getChannelStatus($hotelId) {
        $channels = $this->getActiveChannels($hotelId);
        $status = [];
        
        foreach ($channels as $channel) {
            $lastSync = $this->db->fetch("
                SELECT * FROM sync_logs 
                WHERE hotel_id = ? AND ota_channel_id = ?
                ORDER BY created_at DESC 
                LIMIT 1
            ", [$hotelId, $channel['id']]);
            
            $status[$channel['name']] = [
                'active' => true,
                'last_sync' => $lastSync ? $lastSync['created_at'] : null,
                'last_status' => $lastSync ? $lastSync['status'] : null,
                'last_message' => $lastSync ? $lastSync['message'] : null
            ];
        }
        
        return $status;
    }
}
?>