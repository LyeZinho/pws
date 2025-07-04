# Guia de Integração com APIs e Serviços Externos - MySQL, PHP & ActiveRecord

## Índice
- [Padrões de Integração](#padrões-de-integração)
- [Cliente HTTP Robusto](#cliente-http-robusto)
- [Sincronização de Dados](#sincronização-de-dados)
- [Webhooks e Eventos](#webhooks-e-eventos)
- [Cache e Rate Limiting](#cache-e-rate-limiting)
- [Autenticação](#autenticação)
- [Monitoramento de APIs](#monitoramento-de-apis)
- [Tratamento de Erros](#tratamento-de-erros)
- [Queue e Background Jobs](#queue-e-background-jobs)
- [Exemplos Práticos](#exemplos-práticos)

## Padrões de Integração

### 1. Service Layer Pattern

```php
<?php
// services/ExternalAPIService.php
abstract class ExternalAPIService {
    protected $httpClient;
    protected $config;
    protected $cache;
    protected $logger;
    
    public function __construct($config, $httpClient = null, $cache = null) {
        $this->config = $config;
        $this->httpClient = $httpClient ?: new HttpClient($config);
        $this->cache = $cache ?: new Cache();
        $this->logger = new Logger('api_integration');
    }
    
    abstract protected function getBaseUrl();
    abstract protected function getAuthHeaders();
    
    protected function makeRequest($method, $endpoint, $data = null, $options = []) {
        $url = $this->getBaseUrl() . $endpoint;
        $headers = array_merge($this->getAuthHeaders(), $options['headers'] ?? []);
        
        $cacheKey = $this->getCacheKey($method, $endpoint, $data);
        
        // Try cache for GET requests
        if ($method === 'GET' && isset($options['cache_ttl'])) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                $this->logger->debug("Cache hit for {$url}");
                return $cached;
            }
        }
        
        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => $headers,
                'json' => $data,
                'timeout' => $options['timeout'] ?? 30,
                'retry' => $options['retry'] ?? 3
            ]);
            
            $result = $this->parseResponse($response);
            
            // Cache successful GET requests
            if ($method === 'GET' && isset($options['cache_ttl']) && $response->getStatusCode() === 200) {
                $this->cache->set($cacheKey, $result, $options['cache_ttl']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error("API request failed: {$url}", [
                'method' => $method,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            throw new APIException("API request failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    protected function parseResponse($response) {
        $body = $response->getBody()->getContents();
        
        if ($response->getStatusCode() >= 400) {
            throw new APIException("HTTP {$response->getStatusCode()}: {$body}");
        }
        
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new APIException("Invalid JSON response: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    protected function getCacheKey($method, $endpoint, $data) {
        return 'api_' . md5($method . $endpoint . serialize($data));
    }
}

// Implementação específica
class PaymentAPIService extends ExternalAPIService {
    
    protected function getBaseUrl() {
        return $this->config['payment_api']['base_url'];
    }
    
    protected function getAuthHeaders() {
        return [
            'Authorization' => 'Bearer ' . $this->config['payment_api']['token'],
            'Content-Type' => 'application/json'
        ];
    }
    
    public function createPayment($amount, $currency, $customerId) {
        return $this->makeRequest('POST', '/payments', [
            'amount' => $amount,
            'currency' => $currency,
            'customer_id' => $customerId
        ]);
    }
    
    public function getPayment($paymentId) {
        return $this->makeRequest('GET', "/payments/{$paymentId}", null, [
            'cache_ttl' => 300 // 5 minutos
        ]);
    }
}
?>
```

### 2. Repository Pattern com Sincronização

```php
<?php
// repositories/SyncableRepository.php
class SyncableRepository {
    private $localModel;
    private $externalService;
    private $syncLog;
    
    public function __construct($localModel, $externalService) {
        $this->localModel = $localModel;
        $this->externalService = $externalService;
        $this->syncLog = new SyncLog();
    }
    
    public function sync($options = []) {
        $lastSync = $this->getLastSyncTime();
        $batchSize = $options['batch_size'] ?? 100;
        $fullSync = $options['full_sync'] ?? false;
        
        try {
            if ($fullSync) {
                $this->performFullSync($batchSize);
            } else {
                $this->performIncrementalSync($lastSync, $batchSize);
            }
            
            $this->updateLastSyncTime();
            $this->syncLog->success('Sincronização concluída');
            
        } catch (Exception $e) {
            $this->syncLog->error('Erro na sincronização: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function performIncrementalSync($lastSync, $batchSize) {
        $page = 1;
        
        do {
            $response = $this->externalService->getUpdatedRecords([
                'since' => $lastSync,
                'page' => $page,
                'per_page' => $batchSize
            ]);
            
            foreach ($response['data'] as $externalRecord) {
                $this->syncRecord($externalRecord);
            }
            
            $page++;
        } while ($response['has_more']);
    }
    
    private function syncRecord($externalRecord) {
        $localRecord = $this->localModel::find_by_external_id($externalRecord['id']);
        
        if ($localRecord) {
            // Update existing record
            if ($this->isNewer($externalRecord, $localRecord)) {
                $this->updateLocalRecord($localRecord, $externalRecord);
                $this->syncLog->info("Atualizado registro {$externalRecord['id']}");
            }
        } else {
            // Create new record
            $this->createLocalRecord($externalRecord);
            $this->syncLog->info("Criado novo registro {$externalRecord['id']}");
        }
    }
    
    private function isNewer($externalRecord, $localRecord) {
        $externalUpdated = strtotime($externalRecord['updated_at']);
        $localUpdated = strtotime($localRecord->updated_at);
        
        return $externalUpdated > $localUpdated;
    }
    
    private function updateLocalRecord($localRecord, $externalRecord) {
        foreach ($this->getMappedFields() as $local => $external) {
            if (isset($externalRecord[$external])) {
                $localRecord->$local = $externalRecord[$external];
            }
        }
        
        $localRecord->external_updated_at = $externalRecord['updated_at'];
        $localRecord->save();
    }
    
    private function createLocalRecord($externalRecord) {
        $data = ['external_id' => $externalRecord['id']];
        
        foreach ($this->getMappedFields() as $local => $external) {
            if (isset($externalRecord[$external])) {
                $data[$local] = $externalRecord[$external];
            }
        }
        
        $data['external_updated_at'] = $externalRecord['updated_at'];
        
        $this->localModel::create($data);
    }
    
    private function getMappedFields() {
        return [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone_number',
            'status' => 'status'
        ];
    }
    
    public function pushToExternal($localRecord) {
        try {
            $externalData = $this->mapToExternalFormat($localRecord);
            
            if ($localRecord->external_id) {
                // Update existing
                $response = $this->externalService->updateRecord(
                    $localRecord->external_id, 
                    $externalData
                );
            } else {
                // Create new
                $response = $this->externalService->createRecord($externalData);
                $localRecord->external_id = $response['id'];
                $localRecord->save();
            }
            
            $this->syncLog->info("Enviado para API externa: {$localRecord->id}");
            return $response;
            
        } catch (Exception $e) {
            $this->syncLog->error("Erro ao enviar para API: {$e->getMessage()}");
            throw $e;
        }
    }
}

// Model com sincronização automática
class SyncableUser extends ActiveRecord\Model {
    static $after_save = ['syncToExternal'];
    static $after_destroy = ['deleteFromExternal'];
    
    public function syncToExternal() {
        if ($this->should_sync_to_external) {
            $repository = new SyncableRepository('SyncableUser', new ExternalUserService());
            $repository->pushToExternal($this);
        }
    }
    
    public function deleteFromExternal() {
        if ($this->external_id) {
            $service = new ExternalUserService();
            $service->deleteRecord($this->external_id);
        }
    }
}
?>
```

## Cliente HTTP Robusto

```php
<?php
class HttpClient {
    private $config;
    private $rateLimiter;
    private $circuit;
    
    public function __construct($config) {
        $this->config = $config;
        $this->rateLimiter = new RateLimiter($config['rate_limit'] ?? []);
        $this->circuit = new CircuitBreaker($config['circuit_breaker'] ?? []);
    }
    
    public function request($method, $url, $options = []) {
        // Rate limiting
        $this->rateLimiter->wait();
        
        // Circuit breaker
        if ($this->circuit->isOpen()) {
            throw new ServiceUnavailableException("Circuit breaker is open");
        }
        
        $retries = $options['retry'] ?? 3;
        $backoff = $options['backoff'] ?? 1000; // milliseconds
        
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = $this->makeHttpRequest($method, $url, $options);
                
                // Success - reset circuit breaker
                $this->circuit->recordSuccess();
                
                return $response;
                
            } catch (Exception $e) {
                // Record failure
                $this->circuit->recordFailure();
                
                if ($attempt === $retries) {
                    throw $e;
                }
                
                // Exponential backoff
                $delay = $backoff * pow(2, $attempt - 1);
                usleep($delay * 1000);
            }
        }
    }
    
    private function makeHttpRequest($method, $url, $options) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $options['timeout'] ?? 30,
            CURLOPT_CONNECTTIMEOUT => $options['connect_timeout'] ?? 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => $this->config['user_agent'] ?? 'PHP API Client/1.0'
        ]);
        
        // Method specific options
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        // Headers
        if (isset($options['headers'])) {
            $headers = [];
            foreach ($options['headers'] as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Body data
        if (isset($options['json'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json']));
        } elseif (isset($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false) {
            throw new HttpException("CURL Error: {$error}");
        }
        
        return new HttpResponse($httpCode, $response);
    }
}

class RateLimiter {
    private $requests = [];
    private $maxRequests;
    private $timeWindow;
    
    public function __construct($config) {
        $this->maxRequests = $config['max_requests'] ?? 60;
        $this->timeWindow = $config['time_window'] ?? 60; // seconds
    }
    
    public function wait() {
        $now = time();
        
        // Remove old requests
        $this->requests = array_filter($this->requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });
        
        if (count($this->requests) >= $this->maxRequests) {
            $oldestRequest = min($this->requests);
            $waitTime = $this->timeWindow - ($now - $oldestRequest);
            sleep($waitTime);
        }
        
        $this->requests[] = $now;
    }
}

class CircuitBreaker {
    private $failureThreshold;
    private $recoveryTimeout;
    private $state;
    private $failures;
    private $lastFailureTime;
    
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';
    
    public function __construct($config) {
        $this->failureThreshold = $config['failure_threshold'] ?? 5;
        $this->recoveryTimeout = $config['recovery_timeout'] ?? 60;
        $this->state = self::STATE_CLOSED;
        $this->failures = 0;
        $this->lastFailureTime = null;
    }
    
    public function isOpen() {
        if ($this->state === self::STATE_OPEN) {
            if (time() - $this->lastFailureTime > $this->recoveryTimeout) {
                $this->state = self::STATE_HALF_OPEN;
                return false;
            }
            return true;
        }
        
        return false;
    }
    
    public function recordFailure() {
        $this->failures++;
        $this->lastFailureTime = time();
        
        if ($this->failures >= $this->failureThreshold) {
            $this->state = self::STATE_OPEN;
        }
    }
    
    public function recordSuccess() {
        $this->failures = 0;
        $this->state = self::STATE_CLOSED;
    }
}
?>
```

## Webhooks e Eventos

```php
<?php
// WebhookHandler.php
class WebhookHandler {
    private $config;
    private $logger;
    private $validator;
    
    public function __construct($config) {
        $this->config = $config;
        $this->logger = new Logger('webhooks');
        $this->validator = new WebhookValidator($config);
    }
    
    public function handle($provider, $payload, $headers) {
        try {
            // Validate webhook
            if (!$this->validator->validate($provider, $payload, $headers)) {
                throw new InvalidWebhookException("Invalid webhook signature");
            }
            
            // Log incoming webhook
            $this->logger->info("Webhook received from {$provider}", [
                'payload' => $payload,
                'headers' => $headers
            ]);
            
            // Process webhook
            $processor = $this->getProcessor($provider);
            $result = $processor->process($payload);
            
            // Store webhook event
            $this->storeWebhookEvent($provider, $payload, 'processed', $result);
            
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error("Webhook processing failed", [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            
            $this->storeWebhookEvent($provider, $payload, 'failed', ['error' => $e->getMessage()]);
            
            throw $e;
        }
    }
    
    private function getProcessor($provider) {
        $processors = [
            'stripe' => new StripeWebhookProcessor(),
            'paypal' => new PayPalWebhookProcessor(),
            'mailgun' => new MailgunWebhookProcessor()
        ];
        
        if (!isset($processors[$provider])) {
            throw new Exception("Unknown webhook provider: {$provider}");
        }
        
        return $processors[$provider];
    }
    
    private function storeWebhookEvent($provider, $payload, $status, $result) {
        WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $payload['type'] ?? 'unknown',
            'payload' => json_encode($payload),
            'status' => $status,
            'result' => json_encode($result),
            'processed_at' => date('Y-m-d H:i:s')
        ]);
    }
}

// Exemplo de processador específico
class StripeWebhookProcessor {
    
    public function process($payload) {
        $eventType = $payload['type'];
        
        switch ($eventType) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSuccess($payload['data']['object']);
            
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailure($payload['data']['object']);
            
            case 'customer.subscription.created':
                return $this->handleSubscriptionCreated($payload['data']['object']);
            
            default:
                return ['status' => 'ignored', 'reason' => 'Unhandled event type'];
        }
    }
    
    private function handlePaymentSuccess($paymentIntent) {
        $order = Order::find_by_payment_intent_id($paymentIntent['id']);
        
        if ($order) {
            $order->status = 'paid';
            $order->paid_at = date('Y-m-d H:i:s');
            $order->save();
            
            // Trigger additional actions
            $this->sendPaymentConfirmationEmail($order);
            $this->updateInventory($order);
            
            return ['status' => 'processed', 'order_id' => $order->id];
        }
        
        return ['status' => 'ignored', 'reason' => 'Order not found'];
    }
}

// Model para armazenar eventos de webhook
class WebhookEvent extends ActiveRecord\Model {
    static $table_name = 'webhook_events';
    
    static $validates_presence_of = [
        ['provider'],
        ['event_type'],
        ['payload'],
        ['status']
    ];
    
    public function replay() {
        $handler = new WebhookHandler(Config::get('webhooks'));
        $payload = json_decode($this->payload, true);
        
        try {
            $result = $handler->handle($this->provider, $payload, []);
            
            $this->status = 'replayed';
            $this->result = json_encode($result);
            $this->save();
            
            return $result;
            
        } catch (Exception $e) {
            $this->status = 'replay_failed';
            $this->result = json_encode(['error' => $e->getMessage()]);
            $this->save();
            
            throw $e;
        }
    }
}
?>
```

## Queue e Background Jobs

```php
<?php
// Queue system for API integrations
class APIQueue {
    private $redis;
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
        $this->redis = new Redis();
        $this->redis->connect($config['redis']['host'], $config['redis']['port']);
    }
    
    public function push($queue, $job, $data = [], $delay = 0) {
        $payload = [
            'id' => uniqid(),
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
            'created_at' => time()
        ];
        
        if ($delay > 0) {
            $this->redis->zadd("queue:delayed", time() + $delay, json_encode($payload));
        } else {
            $this->redis->lpush("queue:{$queue}", json_encode($payload));
        }
        
        return $payload['id'];
    }
    
    public function pop($queue, $timeout = 10) {
        // Check for delayed jobs
        $this->moveDelayedJobs();
        
        $job = $this->redis->brpop(["queue:{$queue}"], $timeout);
        
        if ($job) {
            return json_decode($job[1], true);
        }
        
        return null;
    }
    
    private function moveDelayedJobs() {
        $now = time();
        $jobs = $this->redis->zrangebyscore("queue:delayed", 0, $now);
        
        foreach ($jobs as $job) {
            $payload = json_decode($job, true);
            $this->redis->lpush("queue:api", $job);
            $this->redis->zrem("queue:delayed", $job);
        }
    }
    
    public function retry($job, $delay = null) {
        $job['attempts']++;
        
        if ($job['attempts'] >= $this->config['max_attempts']) {
            $this->fail($job);
            return false;
        }
        
        $delay = $delay ?: $this->calculateBackoff($job['attempts']);
        $this->push('api', $job['job'], $job['data'], $delay);
        
        return true;
    }
    
    private function calculateBackoff($attempts) {
        return min(pow(2, $attempts) * 60, 3600); // Max 1 hour
    }
    
    private function fail($job) {
        FailedJob::create([
            'queue' => 'api',
            'payload' => json_encode($job),
            'exception' => 'Max attempts exceeded',
            'failed_at' => date('Y-m-d H:i:s')
        ]);
    }
}

// Worker
class APIWorker {
    private $queue;
    private $logger;
    private $running = true;
    
    public function __construct($queue) {
        $this->queue = $queue;
        $this->logger = new Logger('api_worker');
        
        // Handle graceful shutdown
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
        pcntl_signal(SIGINT, [$this, 'shutdown']);
    }
    
    public function run() {
        $this->logger->info("API Worker started");
        
        while ($this->running) {
            pcntl_signal_dispatch();
            
            $job = $this->queue->pop('api', 5);
            
            if ($job) {
                $this->processJob($job);
            }
        }
        
        $this->logger->info("API Worker stopped");
    }
    
    private function processJob($job) {
        $this->logger->info("Processing job: {$job['job']}", $job['data']);
        
        try {
            $handler = $this->getJobHandler($job['job']);
            $handler->handle($job['data']);
            
            $this->logger->info("Job completed: {$job['id']}");
            
        } catch (Exception $e) {
            $this->logger->error("Job failed: {$job['id']}", [
                'error' => $e->getMessage(),
                'attempts' => $job['attempts']
            ]);
            
            if (!$this->queue->retry($job)) {
                $this->logger->error("Job failed permanently: {$job['id']}");
            }
        }
    }
    
    private function getJobHandler($jobClass) {
        $handlers = [
            'SyncUserJob' => new SyncUserJob(),
            'SendEmailJob' => new SendEmailJob(),
            'ProcessPaymentJob' => new ProcessPaymentJob()
        ];
        
        if (!isset($handlers[$jobClass])) {
            throw new Exception("Unknown job handler: {$jobClass}");
        }
        
        return $handlers[$jobClass];
    }
    
    public function shutdown() {
        $this->running = false;
        $this->logger->info("Shutdown signal received");
    }
}

// Exemplo de job
class SyncUserJob {
    
    public function handle($data) {
        $userId = $data['user_id'];
        $user = User::find($userId);
        
        if (!$user) {
            throw new Exception("User not found: {$userId}");
        }
        
        $service = new ExternalUserService();
        $repository = new SyncableRepository('User', $service);
        
        $repository->pushToExternal($user);
    }
}
?>
```

## Exemplos Práticos

### 1. Integração com API de CEP

```php
<?php
class CEPService extends ExternalAPIService {
    
    protected function getBaseUrl() {
        return 'https://viacep.com.br/ws/';
    }
    
    protected function getAuthHeaders() {
        return ['Content-Type' => 'application/json'];
    }
    
    public function buscarCEP($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        
        if (strlen($cep) !== 8) {
            throw new InvalidArgumentException("CEP deve ter 8 dígitos");
        }
        
        try {
            $response = $this->makeRequest('GET', "{$cep}/json/", null, [
                'cache_ttl' => 86400 // Cache por 24 horas
            ]);
            
            if (isset($response['erro'])) {
                throw new CEPNotFoundException("CEP não encontrado: {$cep}");
            }
            
            return [
                'cep' => $response['cep'],
                'logradouro' => $response['logradouro'],
                'bairro' => $response['bairro'],
                'cidade' => $response['localidade'],
                'uf' => $response['uf'],
                'ibge' => $response['ibge']
            ];
            
        } catch (APIException $e) {
            $this->logger->error("Erro ao buscar CEP: {$cep}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

// Uso no modelo
class Address extends ActiveRecord\Model {
    static $before_save = ['fillAddressData'];
    
    public function fillAddressData() {
        if ($this->cep && !$this->cidade) {
            try {
                $cepService = new CEPService([]);
                $data = $cepService->buscarCEP($this->cep);
                
                $this->logradouro = $data['logradouro'];
                $this->bairro = $data['bairro'];
                $this->cidade = $data['cidade'];
                $this->uf = $data['uf'];
                
            } catch (Exception $e) {
                // Log but don't fail save
                error_log("Failed to fetch CEP data: " . $e->getMessage());
            }
        }
    }
}
?>
```

### 2. Integração com API de Pagamento

```php
<?php
class PaymentGateway extends ExternalAPIService {
    
    protected function getBaseUrl() {
        return $this->config['sandbox'] ? 
            'https://api.sandbox.payment.com' : 
            'https://api.payment.com';
    }
    
    protected function getAuthHeaders() {
        return [
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json'
        ];
    }
    
    public function createPayment($amount, $currency, $customerId, $description = null) {
        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'customer_id' => $customerId,
            'description' => $description,
            'metadata' => [
                'source' => 'php_app',
                'version' => '1.0'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/payments', $data);
        
        // Store payment record
        Payment::create([
            'external_id' => $response['id'],
            'amount' => $amount,
            'currency' => $currency,
            'customer_id' => $customerId,
            'status' => $response['status'],
            'payment_url' => $response['checkout_url']
        ]);
        
        return $response;
    }
    
    public function refundPayment($paymentId, $amount = null) {
        $data = [];
        if ($amount !== null) {
            $data['amount'] = $amount;
        }
        
        $response = $this->makeRequest('POST', "/payments/{$paymentId}/refunds", $data);
        
        // Update local payment record
        $payment = Payment::find_by_external_id($paymentId);
        if ($payment) {
            $payment->status = 'refunded';
            $payment->refunded_at = date('Y-m-d H:i:s');
            $payment->save();
        }
        
        return $response;
    }
}
?>
```

### 3. Integração com Sistema de Email

```php
<?php
class EmailService extends ExternalAPIService {
    
    protected function getBaseUrl() {
        return 'https://api.emailservice.com/v3/';
    }
    
    protected function getAuthHeaders() {
        return [
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json'
        ];
    }
    
    public function sendEmail($to, $subject, $content, $templateId = null) {
        $data = [
            'to' => is_array($to) ? $to : [$to],
            'subject' => $subject,
            'from' => $this->config['from_email']
        ];
        
        if ($templateId) {
            $data['template_id'] = $templateId;
            $data['template_data'] = $content;
        } else {
            $data['html_content'] = $content;
        }
        
        $response = $this->makeRequest('POST', 'mail/send', $data);
        
        // Log email
        EmailLog::create([
            'to_email' => is_array($to) ? implode(',', $to) : $to,
            'subject' => $subject,
            'template_id' => $templateId,
            'external_id' => $response['message_id'],
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        
        return $response;
    }
    
    public function getEmailStatus($messageId) {
        return $this->makeRequest('GET', "mail/status/{$messageId}", null, [
            'cache_ttl' => 300
        ]);
    }
}

// Background job para envio de emails
class SendEmailJob {
    
    public function handle($data) {
        $emailService = new EmailService(Config::get('email_api'));
        
        $result = $emailService->sendEmail(
            $data['to'],
            $data['subject'],
            $data['content'],
            $data['template_id'] ?? null
        );
        
        return $result;
    }
}
?>
```

---

**Importante**: Sempre implemente tratamento de erros robusto, rate limiting, e monitoramento adequado para integrações com APIs externas. Considere usar filas para operações que podem falhar ou demorar.
