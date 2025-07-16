<?php
class ExpediaAdapter {
    private $apiTimeout;
    private $retryAttempts;
    
    public function __construct() {
        $this->apiTimeout = OTA_TIMEOUT;
        $this->retryAttempts = OTA_RETRY_ATTEMPTS;
    }
    
    public function updateRates($channel, $data) {
        $url = $channel['api_endpoint'] . 'rates';
        $jsonData = $this->formatRatesJSON($channel, $data);
        
        return $this->makeApiCall('POST', $url, $jsonData, $channel);
    }
    
    public function updateAvailability($channel, $data) {
        $url = $channel['api_endpoint'] . 'availability';
        $jsonData = $this->formatAvailabilityJSON($channel, $data);
        
        return $this->makeApiCall('POST', $url, $jsonData, $channel);
    }
    
    public function updateRestrictions($channel, $data) {
        $url = $channel['api_endpoint'] . 'restrictions';
        $jsonData = $this->formatRestrictionsJSON($channel, $data);
        
        return $this->makeApiCall('POST', $url, $jsonData, $channel);
    }
    
    public function fetchReservations($channel, $startDate = null, $endDate = null) {
        $url = $channel['api_endpoint'] . 'reservations';
        
        $params = [
            'hotel_id' => $channel['mapping']['ota_hotel_id'],
            'from_date' => $startDate ?: date('Y-m-d'),
            'to_date' => $endDate ?: date('Y-m-d', strtotime('+30 days'))
        ];
        
        $jsonData = $this->formatReservationRequestJSON($channel, $params);
        
        $response = $this->makeApiCall('POST', $url, $jsonData, $channel);
        
        if ($response['success']) {
            $reservations = $this->parseReservationsJSON($response['data']);
            $response['data'] = $reservations;
        }
        
        return $response;
    }
    
    public function formatRateData($inventory) {
        $formattedData = [];
        
        foreach ($inventory as $item) {
            $formattedData[] = [
                'date' => $item['date'],
                'rate' => $item['rate'],
                'currency' => 'USD'
            ];
        }
        
        return $formattedData;
    }
    
    public function formatAvailabilityData($inventory) {
        $formattedData = [];
        
        foreach ($inventory as $item) {
            $formattedData[] = [
                'date' => $item['date'],
                'available' => $item['available_rooms'],
                'total' => $item['total_rooms']
            ];
        }
        
        return $formattedData;
    }
    
    public function formatRestrictionsData($inventory) {
        $formattedData = [];
        
        foreach ($inventory as $item) {
            $formattedData[] = [
                'date' => $item['date'],
                'min_stay' => $item['min_stay'],
                'max_stay' => $item['max_stay'],
                'closed_to_arrival' => $item['closed_to_arrival'],
                'closed_to_departure' => $item['closed_to_departure']
            ];
        }
        
        return $formattedData;
    }
    
    private function formatRatesJSON($channel, $data) {
        $payload = [
            'authentication' => [
                'username' => $channel['api_key'],
                'password' => $channel['api_secret']
            ],
            'hotel' => [
                'hotelId' => $channel['mapping']['ota_hotel_id']
            ],
            'rates' => []
        ];
        
        foreach ($data as $rate) {
            $payload['rates'][] = [
                'date' => $rate['date'],
                'amount' => $rate['rate'],
                'currency' => $rate['currency']
            ];
        }
        
        return json_encode($payload);
    }
    
    private function formatAvailabilityJSON($channel, $data) {
        $payload = [
            'authentication' => [
                'username' => $channel['api_key'],
                'password' => $channel['api_secret']
            ],
            'hotel' => [
                'hotelId' => $channel['mapping']['ota_hotel_id']
            ],
            'availability' => []
        ];
        
        foreach ($data as $avail) {
            $payload['availability'][] = [
                'date' => $avail['date'],
                'available' => $avail['available'],
                'total' => $avail['total']
            ];
        }
        
        return json_encode($payload);
    }
    
    private function formatRestrictionsJSON($channel, $data) {
        $payload = [
            'authentication' => [
                'username' => $channel['api_key'],
                'password' => $channel['api_secret']
            ],
            'hotel' => [
                'hotelId' => $channel['mapping']['ota_hotel_id']
            ],
            'restrictions' => []
        ];
        
        foreach ($data as $restriction) {
            $payload['restrictions'][] = [
                'date' => $restriction['date'],
                'minStay' => $restriction['min_stay'],
                'maxStay' => $restriction['max_stay'],
                'closedToArrival' => $restriction['closed_to_arrival'],
                'closedToDeparture' => $restriction['closed_to_departure']
            ];
        }
        
        return json_encode($payload);
    }
    
    private function formatReservationRequestJSON($channel, $params) {
        $payload = [
            'authentication' => [
                'username' => $channel['api_key'],
                'password' => $channel['api_secret']
            ],
            'hotel' => [
                'hotelId' => $params['hotel_id']
            ],
            'dateRange' => [
                'from' => $params['from_date'],
                'to' => $params['to_date']
            ]
        ];
        
        return json_encode($payload);
    }
    
    private function parseReservationsJSON($jsonResponse) {
        $reservations = [];
        
        try {
            $data = json_decode($jsonResponse, true);
            
            if ($data && isset($data['reservations'])) {
                foreach ($data['reservations'] as $reservation) {
                    $reservations[] = [
                        'id' => $reservation['id'],
                        'guest_name' => $reservation['guestName'],
                        'guest_email' => $reservation['guestEmail'] ?? '',
                        'guest_phone' => $reservation['guestPhone'] ?? '',
                        'room_type' => $reservation['roomType'],
                        'check_in' => $reservation['checkIn'],
                        'check_out' => $reservation['checkOut'],
                        'adults' => $reservation['adults'] ?? 1,
                        'children' => $reservation['children'] ?? 0,
                        'total_amount' => $reservation['totalAmount'],
                        'currency' => $reservation['currency'],
                        'status' => $reservation['status'],
                        'special_requests' => $reservation['specialRequests'] ?? ''
                    ];
                }
            }
        } catch (Exception $e) {
            Logger::error("Error parsing Expedia reservations JSON: " . $e->getMessage());
        }
        
        return $reservations;
    }
    
    private function makeApiCall($method, $url, $data, $channel) {
        $startTime = microtime(true);
        
        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->apiTimeout,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data),
                    'Accept: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response === false || !empty($error)) {
                Logger::error("Expedia API call failed (attempt $attempt): " . $error);
                
                if ($attempt === $this->retryAttempts) {
                    return [
                        'success' => false,
                        'message' => 'API call failed after ' . $this->retryAttempts . ' attempts: ' . $error,
                        'data' => null
                    ];
                }
                
                sleep(pow(2, $attempt)); // Exponential backoff
                continue;
            }
            
            Logger::logApiCall($method, $url, $data, $response, $duration);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message' => 'API call successful',
                    'data' => $response
                ];
            } else {
                $errorMessage = "HTTP $httpCode: " . $this->parseErrorResponse($response);
                
                if ($attempt === $this->retryAttempts) {
                    return [
                        'success' => false,
                        'message' => $errorMessage,
                        'data' => $response
                    ];
                }
                
                sleep(pow(2, $attempt)); // Exponential backoff
            }
        }
        
        return [
            'success' => false,
            'message' => 'Unknown error',
            'data' => null
        ];
    }
    
    private function parseErrorResponse($response) {
        try {
            $data = json_decode($response, true);
            if ($data && isset($data['error'])) {
                return $data['error'];
            }
        } catch (Exception $e) {
            // Ignore parsing errors
        }
        
        return 'Unknown error';
    }
}
?>