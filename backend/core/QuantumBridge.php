<?php
/**
 * =============================================================================
 * Quantum Bridge - PHP to Python Interface
 * =============================================================================
 * Abstracts communication with the Python quantum service modules.
 * 
 * This class handles:
 *   - Calling Python scripts with JSON input/output
 *   - Error handling and timeout management
 *   - Parsing quantum service responses
 * 
 * Services:
 *   - sign()      - Dilithium3 digital signatures
 *   - encrypt()   - Kyber512 hybrid encryption
 *   - entropy()   - Quantum random number generation
 *   - decrypt()   - Kyber512 hybrid decryption
 *   - verify()    - Signature verification
 * 
 * Usage:
 *   $bridge = new QuantumBridge();
 *   $signature = $bridge->sign($ticketData);
 *   $encrypted = $bridge->encrypt($passportNumber);
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Core;

use RuntimeException;
use JsonException;

class QuantumBridge
{
    /**
     * Path to Python interpreter
     */
    private string $pythonPath;
    
    /**
     * Path to quantum service directory
     */
    private string $servicePath;
    
    /**
     * Execution timeout in seconds
     */
    private int $timeout;
    
    /**
     * Last execution metadata
     */
    private array $lastExecution = [];
    
    /**
     * Constructor
     * 
     * @param string|null $pythonPath Path to Python interpreter (auto-detected if null)
     * @param string|null $servicePath Path to quantum_service directory
     * @param int $timeout Execution timeout in seconds
     */
    public function __construct(
        ?string $pythonPath = null,
        ?string $servicePath = null,
        int $timeout = 30
    ) {
        $this->pythonPath = $pythonPath ?? $this->detectPython();
        $this->servicePath = $servicePath ?? $this->getDefaultServicePath();
        $this->timeout = $timeout;
        
        $this->validateSetup();
    }
    
    /**
     * Detect Python interpreter
     */
    private function detectPython(): string
    {
        // Try common Python paths
        $paths = ['python3', 'python', '/usr/bin/python3', '/usr/local/bin/python3'];
        
        foreach ($paths as $path) {
            $output = shell_exec("which {$path} 2>/dev/null");
            if ($output !== null && trim($output) !== '') {
                return trim($output);
            }
        }
        
        // Default fallback
        return 'python3';
    }
    
    /**
     * Get default service path
     */
    private function getDefaultServicePath(): string
    {
        return dirname(__DIR__, 2) . '/quantum_service';
    }
    
    /**
     * Validate the quantum service setup
     */
    private function validateSetup(): void
    {
        if (!is_dir($this->servicePath)) {
            throw new RuntimeException(
                "Quantum service directory not found: {$this->servicePath}"
            );
        }
        
        $requiredScripts = ['signer.py', 'encryptor.py', 'entropy.py', 'decryptor.py'];
        foreach ($requiredScripts as $script) {
            $scriptPath = $this->servicePath . '/' . $script;
            if (!file_exists($scriptPath)) {
                throw new RuntimeException(
                    "Required quantum script not found: {$scriptPath}"
                );
            }
        }
    }
    
    /**
     * Execute a Python script with JSON input
     * 
     * @param string $script Script filename (e.g., 'signer.py')
     * @param array $input Input data to pass as JSON
     * @return array Parsed JSON response
     * @throws RuntimeException On execution failure
     */
    private function execute(string $script, array $input): array
    {
        $scriptPath = $this->servicePath . '/' . $script;
        $inputJson = json_encode($input, JSON_THROW_ON_ERROR);
        
        // Escape for shell
        $escapedInput = escapeshellarg($inputJson);
        $escapedPython = escapeshellarg($this->pythonPath);
        $escapedScript = escapeshellarg($scriptPath);
        
        // Build command with timeout
        $command = "timeout {$this->timeout}s {$escapedPython} {$escapedScript} {$escapedInput} 2>&1";
        
        // Record execution start
        $startTime = microtime(true);
        
        // Execute
        $output = shell_exec($command);
        
        // Record execution metadata
        $this->lastExecution = [
            'script' => $script,
            'input' => $input,
            'output' => $output,
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'command' => $command,
        ];
        
        if ($output === null || $output === '') {
            throw new RuntimeException(
                "Quantum service returned empty response. Script: {$script}"
            );
        }
        
        // Parse JSON response
        try {
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(
                "Invalid JSON from quantum service: {$output}. Error: {$e->getMessage()}"
            );
        }
        
        // Check for error response
        if (isset($result['success']) && $result['success'] === false) {
            throw new RuntimeException(
                "Quantum service error: " . ($result['error'] ?? 'Unknown error')
            );
        }
        
        return $result;
    }
    
    /**
     * Sign data using Dilithium3
     * 
     * @param string $data Data to sign (typically JSON-encoded ticket data)
     * @return array Contains: signature, public_key, data_hash, algorithm, mock_mode
     */
    public function sign(string $data): array
    {
        return $this->execute('signer.py', ['data' => $data]);
    }
    
    /**
     * Verify a Dilithium3 signature
     * 
     * @param string $data Original signed data
     * @param string $signature Hex-encoded signature
     * @param string $publicKey Hex-encoded public key
     * @return array Contains: valid (bool), algorithm, mock_mode
     */
    public function verify(string $data, string $signature, string $publicKey): array
    {
        return $this->execute('signer.py', [
            'verify' => true,
            'data' => $data,
            'signature' => $signature,
            'public_key' => $publicKey,
        ]);
    }
    
    /**
     * Encrypt data using Kyber512 hybrid encryption
     * 
     * @param string $plaintext Data to encrypt (e.g., passport number)
     * @return array Contains: ciphertext, encapsulated_key, nonce, public_key, private_key, algorithm, mock_mode
     */
    public function encrypt(string $plaintext): array
    {
        return $this->execute('encryptor.py', ['plaintext' => $plaintext]);
    }
    
    /**
     * Decrypt data encrypted with Kyber512
     * 
     * @param string $ciphertext Base64-encoded ciphertext
     * @param string $encapsulatedKey Base64-encoded encapsulated key
     * @param string $nonce Base64-encoded nonce
     * @param string $privateKey Hex-encoded Kyber private key
     * @return array Contains: plaintext, algorithm, mock_mode
     */
    public function decrypt(
        string $ciphertext,
        string $encapsulatedKey,
        string $nonce,
        string $privateKey
    ): array {
        return $this->execute('decryptor.py', [
            'ciphertext' => $ciphertext,
            'encapsulated_key' => $encapsulatedKey,
            'nonce' => $nonce,
            'private_key' => $privateKey,
        ]);
    }
    
    /**
     * Generate a quantum random booking reference
     * 
     * @param int $length Length of the reference (default 8)
     * @return array Contains: random_id, method, algorithm, mock_mode
     */
    public function generateBookingRef(int $length = 8): array
    {
        return $this->execute('entropy.py', ['length' => $length]);
    }
    
    /**
     * Get the last execution metadata
     * 
     * Useful for debugging and logging.
     */
    public function getLastExecution(): array
    {
        return $this->lastExecution;
    }
    
    /**
     * Check if the quantum service is available and working
     * 
     * @return array Service status information
     */
    public function healthCheck(): array
    {
        try {
            // Test each service
            $entropy = $this->generateBookingRef(4);
            $encrypt = $this->encrypt('test');
            $sign = $this->sign('test');
            
            return [
                'status' => 'healthy',
                'services' => [
                    'entropy' => [
                        'available' => true,
                        'mock_mode' => $entropy['mock_mode'] ?? false,
                    ],
                    'encryption' => [
                        'available' => true,
                        'mock_mode' => $encrypt['mock_mode'] ?? false,
                    ],
                    'signing' => [
                        'available' => true,
                        'mock_mode' => $sign['mock_mode'] ?? false,
                    ],
                ],
                'python_path' => $this->pythonPath,
                'service_path' => $this->servicePath,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'python_path' => $this->pythonPath,
                'service_path' => $this->servicePath,
            ];
        }
    }
}
