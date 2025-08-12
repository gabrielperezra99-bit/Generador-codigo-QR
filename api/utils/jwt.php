<?php
class JWTHandler {
    private $secret_key = "tu_clave_secreta_muy_segura_2024";
    private $issuer = "planos-qr-app";
    private $audience = "planos-qr-users";
    private $issuedAt;
    private $expire;

    public function __construct() {
        $this->issuedAt = time();
        $this->expire = $this->issuedAt + (24 * 60 * 60); // 24 horas
    }

    public function generateToken($user_data) {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        $payload = json_encode([
            "iss" => $this->issuer,
            "aud" => $this->audience,
            "iat" => $this->issuedAt,
            "exp" => $this->expire,
            "data" => [
                "id" => $user_data['id'],
                "nombre" => $user_data['nombre'],
                "email" => $user_data['email']
            ]
        ]);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret_key, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public function validateToken($token) {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return false;
            }

            list($base64Header, $base64Payload, $base64Signature) = $parts;

            // Verificar la firma
            $signature = $this->base64UrlDecode($base64Signature);
            $expectedSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret_key, true);

            if (!hash_equals($signature, $expectedSignature)) {
                return false;
            }

            // Decodificar el payload
            $payload = json_decode($this->base64UrlDecode($base64Payload), true);

            if (!$payload) {
                return false;
            }

            // Verificar expiración
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }

            // Verificar issuer y audience
            if (isset($payload['iss']) && $payload['iss'] !== $this->issuer) {
                return false;
            }

            if (isset($payload['aud']) && $payload['aud'] !== $this->audience) {
                return false;
            }

            return $payload['data'] ?? false;

        } catch (Exception $e) {
            return false;
        }
    }

    public function getTokenFromHeader() {
        // Obtener headers de diferentes maneras para compatibilidad
        $headers = null;
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback para servidores que no tienen getallheaders()
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        // Buscar el header Authorization
        $authHeader = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }

        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7); // Remover "Bearer "
        }

        return null;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    // Método para verificar si un token es válido sin decodificarlo completamente
    public function isTokenValid($token) {
        return $this->validateToken($token) !== false;
    }

    // Método para obtener información del token sin validar completamente
    public function getTokenInfo($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>