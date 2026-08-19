<?php
namespace local_swayamplus;

class api {
    public static function get_access_token() {
        $cache = \cache::make('local_swayamplus', 'tokens');
        $token = $cache->get('bearer_token');
        
        if (!$token) {
            $client_id = get_config('local_swayamplus', 'client_id');
            $secret = get_config('local_swayamplus', 'client_secret');
            $base_url = get_config('local_swayamplus', 'swayam_url');
            
            $curl = new \curl();
            $curl->setHeader('Authorization: Basic ' . base64_encode($client_id . ':' . $secret));
            
            $data = [
                'grant_type' => 'client_credentials',
                'scope' => 'partner.enrollments:read partner.completions:write'
            ];
            
            $response = $curl->post($base_url . '/oidc/token', $data);
            $json = json_decode($response);
            
            $token = $json->access_token;
            // Cache token for slightly less than 10 mins (e.g., 550 seconds)
            $cache->set('bearer_token', $token, 550); 
        }
        return $token;
    }

    public static function report_progress($enrollment_id, $percent) {
        $base_url = get_config('local_swayamplus', 'swayam_url');
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . self::get_access_token());
        $curl->setHeader('Content-Type: application/json');
        
        $data = json_encode(['percent' => (int)$percent]); // Ensure integer
        $curl->post($base_url . "/api/v1/partner/enrollments/{$enrollment_id}/progress", $data);
        
        // 409 means already completed, 404 means not started via SSO. Check $curl->get_info()['http_code']
    }

    public static function report_completion($enrollment_id) {
        $base_url = get_config('local_swayamplus', 'swayam_url');
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . self::get_access_token());
        $curl->setHeader('Content-Type: application/json');
        
        $curl->post($base_url . "/api/v1/partner/enrollments/{$enrollment_id}/completion", '{}');
    }
}
