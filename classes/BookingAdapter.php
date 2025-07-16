<?php
class BookingAdapter {
    private $apiTimeout;
    private $retryAttempts;
    
    public function __construct() {
        $this->apiTimeout = OTA_TIMEOUT;
        $this->retryAttempts = OTA_RETRY_ATTEMPTS;
    }
    
    public function updateRates($channel, $data) {
        $url = $channel['api_endpoint'] . 'rates';
        $xmlData = $this->formatRatesXML($channel, $data);
        
        return $this->makeApiCall('POST', $url, $xmlData, $channel);
    }
    
    public function updateAvailability($channel, $data) {
        $url = $channel['api_endpoint'] . 'availability';
        $xmlData = $this->formatAvailabilityXML($channel, $data);
        
        return $this->makeApiCall('POST', $url, $xmlData, $channel);
    }
    
    public function updateRestrictions($channel, $data) {
        $url = $channel['api_endpoint'] . 'restrictions';
        $xmlData = $this->formatRestrictionsXML($channel, $data);
        
        return $this->makeApiCall('POST', $url, $xmlData, $channel);
    }
    
    public function fetchReservations($channel, $startDate = null, $endDate = null) {
        $url = $channel['api_endpoint'] . 'reservations';
        
        $params = [
            'hotel_id' => $channel['mapping']['ota_hotel_id'],
            'from_date' => $startDate ?: date('Y-m-d'),
            'to_date' => $endDate ?: date('Y-m-d', strtotime('+30 days'))
        ];
        
        $xmlData = $this->formatReservationRequestXML($channel, $params);
        
        $response = $this->makeApiCall('POST', $url, $xmlData, $channel);
        
        if ($response['success']) {
            $reservations = $this->parseReservationsXML($response['data']);
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
    
    private function formatRatesXML($channel, $data) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<request>';
        $xml .= '<username>' . htmlspecialchars($channel['api_key']) . '</username>';
        $xml .= '<password>' . htmlspecialchars($channel['api_secret']) . '</password>';
        $xml .= '<hotel_id>' . htmlspecialchars($channel['mapping']['ota_hotel_id']) . '</hotel_id>';
        $xml .= '<rates>';
        
        foreach ($data as $rate) {
            $xml .= '<rate>';
            $xml .= '<date>' . htmlspecialchars($rate['date']) . '</date>';
            $xml .= '<amount>' . htmlspecialchars($rate['rate']) . '</amount>';
            $xml .= '<currency>' . htmlspecialchars($rate['currency']) . '</currency>';
            $xml .= '</rate>';
        }
        
        $xml .= '</rates>';
        $xml .= '</request>';
        
        return $xml;
    }
    
    private function formatAvailabilityXML($channel, $data) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<request>';
        $xml .= '<username>' . htmlspecialchars($channel['api_key']) . '</username>';
        $xml .= '<password>' . htmlspecialchars($channel['api_secret']) . '</password>';
        $xml .= '<hotel_id>' . htmlspecialchars($channel['mapping']['ota_hotel_id']) . '</hotel_id>';
        $xml .= '<availability>';
        
        foreach ($data as $avail) {
            $xml .= '<room>';
            $xml .= '<date>' . htmlspecialchars($avail['date']) . '</date>';
            $xml .= '<available>' . htmlspecialchars($avail['available']) . '</available>';
            $xml .= '<total>' . htmlspecialchars($avail['total']) . '</total>';
            $xml .= '</room>';
        }
        
        $xml .= '</availability>';
        $xml .= '</request>';
        
        return $xml;
    }
    
    private function formatRestrictionsXML($channel, $data) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<request>';
        $xml .= '<username>' . htmlspecialchars($channel['api_key']) . '</username>';
        $xml .= '<password>' . htmlspecialchars($channel['api_secret']) . '</password>';
        $xml .= '<hotel_id>' . htmlspecialchars($channel['mapping']['ota_hotel_id']) . '</hotel_id>';
        $xml .= '<restrictions>';
        
        foreach ($data as $restriction) {
            $xml .= '<restriction>';
            $xml .= '<date>' . htmlspecialchars($restriction['date']) . '</date>';
            $xml .= '<min_stay>' . htmlspecialchars($restriction['min_stay']) . '</min_stay>';
            $xml .= '<max_stay>' . htmlspecialchars($restriction['max_stay']) . '</max_stay>';
            $xml .= '<closed_to_arrival>' . ($restriction['closed_to_arrival'] ? '1' : '0') . '</closed_to_arrival>';
            $xml .= '<closed_to_departure>' . ($restriction['closed_to_departure'] ? '1' : '0') . '</closed_to_departure>';
            $xml .= '</restriction>';
        }
        
        $xml .= '</restrictions>';
        $xml .= '</request>';
        
        return $xml;
    }
    
    private function formatReservationRequestXML($channel, $params) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<request>';
        $xml .= '<username>' . htmlspecialchars($channel['api_key']) . '</username>';
        $xml .= '<password>' . htmlspecialchars($channel['api_secret']) . '</password>';
        $xml .= '<hotel_id>' . htmlspecialchars($params['hotel_id']) . '</hotel_id>';
        $xml .= '<from_date>' . htmlspecialchars($params['from_date']) . '</from_date>';
        $xml .= '<to_date>' . htmlspecialchars($params['to_date']) . '</to_date>';
        $xml .= '</request>';
        
        return $xml;
    }
    
    private function parseReservationsXML($xmlResponse) {
        $reservations = [];
        
        try {
            $xml = simplexml_load_string($xmlResponse);
            
            if ($xml && isset($xml->reservations->reservation)) {
                foreach ($xml->reservations->reservation as $reservation) {
                    $reservations[] = [
                        'id' => (string) $reservation->id,
                        'guest_name' => (string) $reservation->guest_name,
                        'guest_email' => (string) $reservation->guest_email,
                        'guest_phone' => (string) $reservation->guest_phone,
                        'room_type' => (string) $reservation->room_type,
                        'check_in' => (string) $reservation->check_in,
                        'check_out' => (string) $reservation->check_out,
                        'adults' => (int) $reservation->adults,
                        'children' => (int) $reservation->children,
                        'total_amount' => (float) $reservation->total_amount,
                        'currency' => (string) $reservation->currency,
                        'status' => (string) $reservation->status,
                        'special_requests' => (string) $reservation->special_requests
                    ];
                }
            }
        } catch (Exception $e) {
            Logger::error("Error parsing Booking.com reservations XML: " . $e->getMessage());
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
                    'Content-Type: application/xml',
                    'Content-Length: ' . strlen($data)
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
                Logger::error("Booking.com API call failed (attempt $attempt): " . $error);
                
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
            $xml = simplexml_load_string($response);
            if ($xml && isset($xml->error)) {
                return (string) $xml->error;
            }
        } catch (Exception $e) {
            // Ignore parsing errors
        }
        
        return 'Unknown error';
    }
}
?>