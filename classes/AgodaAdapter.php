<?php
class AgodaAdapter {
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
        $xml .= '<YCS_RatesAndAvailabilityRQ>';
        $xml .= '<Authentication>';
        $xml .= '<Username>' . htmlspecialchars($channel['api_key']) . '</Username>';
        $xml .= '<Password>' . htmlspecialchars($channel['api_secret']) . '</Password>';
        $xml .= '</Authentication>';
        $xml .= '<Hotel>';
        $xml .= '<HotelCode>' . htmlspecialchars($channel['mapping']['ota_hotel_id']) . '</HotelCode>';
        $xml .= '<RateAndAvailability>';
        
        foreach ($data as $rate) {
            $xml .= '<RateAvailability>';
            $xml .= '<Date>' . htmlspecialchars($rate['date']) . '</Date>';
            $xml .= '<Rate>' . htmlspecialchars($rate['rate']) . '</Rate>';
            $xml .= '<Currency>' . htmlspecialchars($rate['currency']) . '</Currency>';
            $xml .= '</RateAvailability>';
        }
        
        $xml .= '</RateAndAvailability>';
        $xml .= '</Hotel>';
        $xml .= '</YCS_RatesAndAvailabilityRQ>';
        
        return $xml;
    }
    
    private function formatAvailabilityXML($channel, $data) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<YCS_RatesAndAvailabilityRQ>';
        $xml .= '<Authentication>';
        $xml .= '<Username>' . htmlspecialchars($channel['api_key']) . '</Username>';
        $xml .= '<Password>' . htmlspecialchars($channel['api_secret']) . '</Password>';
        $xml .= '</Authentication>';
        $xml .= '<Hotel>';
        $xml .= '<HotelCode>' . htmlspecialchars($channel['mapping']['ota_hotel_id']) . '</HotelCode>';
        $xml .= '<RateAndAvailability>';
        
        foreach ($data as $avail) {
            $xml .= '<RateAvailability>';
            $xml .= '<Date>' . htmlspecialchars($avail['date']) . '</Date>';
            $xml .= '<Available>' . htmlspecialchars($avail['available']) . '</Available>';
            $xml .= '<Total>' . htmlspecialchars($avail['total']) . '</Total>';
            $xml .= '</RateAvailability>';
        }
        
        $xml .= '</RateAndAvailability>';
        $xml .= '</Hotel>';
        $xml .= '</YCS_RatesAndAvailabilityRQ>';
        
        return $xml;
    }
    
    private function formatRestrictionsXML($channel, $data) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<YCS_RestrictionsRQ>';
        $xml .= '<Authentication>';
        $xml .= '<Username>' . htmlspecialchars($channel['api_key']) . '</Username>';
        $xml .= '<Password>' . htmlspecialchars($channel['api_secret']) . '</Password>';
        $xml .= '</Authentication>';
        $xml .= '<Hotel>';
        $xml .= '<HotelCode>' . htmlspecialchars($channel['mapping']['ota_hotel_id']) . '</HotelCode>';
        $xml .= '<Restrictions>';
        
        foreach ($data as $restriction) {
            $xml .= '<Restriction>';
            $xml .= '<Date>' . htmlspecialchars($restriction['date']) . '</Date>';
            $xml .= '<MinStay>' . htmlspecialchars($restriction['min_stay']) . '</MinStay>';
            $xml .= '<MaxStay>' . htmlspecialchars($restriction['max_stay']) . '</MaxStay>';
            $xml .= '<ClosedToArrival>' . ($restriction['closed_to_arrival'] ? 'true' : 'false') . '</ClosedToArrival>';
            $xml .= '<ClosedToDeparture>' . ($restriction['closed_to_departure'] ? 'true' : 'false') . '</ClosedToDeparture>';
            $xml .= '</Restriction>';
        }
        
        $xml .= '</Restrictions>';
        $xml .= '</Hotel>';
        $xml .= '</YCS_RestrictionsRQ>';
        
        return $xml;
    }
    
    private function formatReservationRequestXML($channel, $params) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<YCS_ReservationRQ>';
        $xml .= '<Authentication>';
        $xml .= '<Username>' . htmlspecialchars($channel['api_key']) . '</Username>';
        $xml .= '<Password>' . htmlspecialchars($channel['api_secret']) . '</Password>';
        $xml .= '</Authentication>';
        $xml .= '<Hotel>';
        $xml .= '<HotelCode>' . htmlspecialchars($params['hotel_id']) . '</HotelCode>';
        $xml .= '<DateRange>';
        $xml .= '<From>' . htmlspecialchars($params['from_date']) . '</From>';
        $xml .= '<To>' . htmlspecialchars($params['to_date']) . '</To>';
        $xml .= '</DateRange>';
        $xml .= '</Hotel>';
        $xml .= '</YCS_ReservationRQ>';
        
        return $xml;
    }
    
    private function parseReservationsXML($xmlResponse) {
        $reservations = [];
        
        try {
            $xml = simplexml_load_string($xmlResponse);
            
            if ($xml && isset($xml->Reservations->Reservation)) {
                foreach ($xml->Reservations->Reservation as $reservation) {
                    $reservations[] = [
                        'id' => (string) $reservation->ReservationId,
                        'guest_name' => (string) $reservation->GuestName,
                        'guest_email' => (string) $reservation->GuestEmail,
                        'guest_phone' => (string) $reservation->GuestPhone,
                        'room_type' => (string) $reservation->RoomType,
                        'check_in' => (string) $reservation->CheckIn,
                        'check_out' => (string) $reservation->CheckOut,
                        'adults' => (int) $reservation->Adults,
                        'children' => (int) $reservation->Children,
                        'total_amount' => (float) $reservation->TotalAmount,
                        'currency' => (string) $reservation->Currency,
                        'status' => (string) $reservation->Status,
                        'special_requests' => (string) $reservation->SpecialRequests
                    ];
                }
            }
        } catch (Exception $e) {
            Logger::error("Error parsing Agoda reservations XML: " . $e->getMessage());
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
                    'Content-Length: ' . strlen($data),
                    'SOAPAction: ""'
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
                Logger::error("Agoda API call failed (attempt $attempt): " . $error);
                
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
            if ($xml && isset($xml->Error)) {
                return (string) $xml->Error;
            }
        } catch (Exception $e) {
            // Ignore parsing errors
        }
        
        return 'Unknown error';
    }
}
?>