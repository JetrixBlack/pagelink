<?php
/*
 * Cliente HTTP para Turso (libSQL sobre HTTP).
 * Permite ejecutar queries SQL contra Turso usando la misma
 * interfaz que PDO, pero adaptada al protocolo Hrana (HTTP/REST de Turso).
 *
 * USO: require_once __DIR__ . '/turso.php';
 *      $db = getTursoDB();
 *      $rows = $db->query("SELECT * FROM profile WHERE id = 1")->fetchAll();
 */

// ─── Clase TursoStatement — simula PDOStatement ──────────────────────────────
class TursoStatement {
    private array $rows;
    private int   $pointer = 0;

    public function __construct(array $rows) {
        $this->rows = $rows;
    }

    /** Retorna la siguiente fila como array asociativo (simula PDOStatement::fetch) */
    public function fetch(): array|false {
        return $this->rows[$this->pointer++] ?? false;
    }

    /** Retorna todas las filas (simula PDOStatement::fetchAll) */
    public function fetchAll(int $mode = 0, int $col = 0): array {
        if ($mode === \PDO::FETCH_COLUMN) {
            return array_column($this->rows, array_keys($this->rows[0] ?? [])[$col] ?? null);
        }
        return $this->rows;
    }

    /** Retorna el valor de la primera columna de la primera fila */
    public function fetchColumn(int $col = 0): mixed {
        $row = $this->rows[0] ?? [];
        $keys = array_keys($row);
        return $row[$keys[$col] ?? ''] ?? false;
    }
}

// ─── Clase TursoPDO — simula PDO ─────────────────────────────────────────────
class TursoPDO {
    private string $url;
    private string $token;
    private array  $boundParams = [];

    public function __construct(string $url, string $token) {
        // Normalizar URL al endpoint HTTP de Turso
        $this->url   = rtrim($url, '/');
        $this->token = $token;
    }

    /**
     * Envía una o varias sentencias SQL a Turso vía HTTP (protocolo Hrana).
     * Retorna TursoStatement con los resultados de la última sentencia.
     */
    private function execute(string $sql, array $params = []): TursoStatement {
        $payload = [
            'requests' => [
                [
                    'type'  => 'execute',
                    'stmt'  => [
                        'sql'  => $sql,
                        'args' => array_map(fn($p) => $this->encodeParam($p), $params),
                    ],
                ],
                ['type' => 'close'],
            ],
        ];

        $ch = curl_init($this->url . '/v2/pipeline');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            throw new \RuntimeException("Error Turso HTTP $httpCode: $response");
        }

        $data = json_decode($response, true);

        // Verificar errores en la respuesta
        foreach ($data['results'] ?? [] as $result) {
            if (($result['type'] ?? '') === 'error') {
                throw new \RuntimeException('Error Turso SQL: ' . ($result['error']['message'] ?? 'desconocido'));
            }
        }

        // Extraer filas del primer resultado (execute)
        $resultSet = $data['results'][0] ?? [];
        $rows = $this->parseRows($resultSet['response']['result'] ?? []);

        return new TursoStatement($rows);
    }

    /**
     * Convierte el resultado Hrana (cols + rows) a array asociativo.
     */
    private function parseRows(array $result): array {
        $cols = array_column($result['cols'] ?? [], 'name');
        $out  = [];
        foreach ($result['rows'] ?? [] as $row) {
            $record = [];
            foreach ($row as $i => $cell) {
                $record[$cols[$i]] = $cell['value'] ?? null;
            }
            $out[] = $record;
        }
        return $out;
    }

    /**
     * Codifica un parámetro PHP al formato de valor Hrana de Turso.
     */
    private function encodeParam(mixed $p): array {
        if ($p === null)           return ['type' => 'null'];
        if (is_int($p))            return ['type' => 'integer', 'value' => (string)$p];
        if (is_float($p))          return ['type' => 'float',   'value' => (string)$p];
        return                            ['type' => 'text',    'value' => (string)$p];
    }

    // ─── Interfaz pública compatible con PDO ────────────────────────────────

    /** Ejecuta una query sin parámetros (simula PDO::query) */
    public function query(string $sql): TursoStatement {
        return $this->execute($sql);
    }

    /** Ejecuta una sentencia sin retorno (CREATE, PRAGMA, etc.) */
    public function exec(string $sql): void {
        $this->execute($sql);
    }

    /** Prepara una sentencia con marcadores ? (simula PDO::prepare) */
    public function prepare(string $sql): TursoPrepared {
        return new TursoPrepared($this, $sql);
    }

    /** Método interno para que TursoPrepared ejecute la query */
    public function _run(string $sql, array $params): TursoStatement {
        return $this->execute($sql, $params);
    }
}

// ─── Clase TursoPrepared — simula PDOStatement preparado ─────────────────────
class TursoPrepared {
    private TursoPDO $pdo;
    private string   $sql;
    private ?TursoStatement $lastResult = null;

    public function __construct(TursoPDO $pdo, string $sql) {
        $this->pdo = $pdo;
        $this->sql = $sql;
    }

    /** Ejecuta la sentencia preparada con los parámetros dados */
    public function execute(array $params = []): bool {
        $this->lastResult = $this->pdo->_run($this->sql, $params);
        return true;
    }

    /** Retorna la siguiente fila del resultado (simula PDOStatement::fetch) */
    public function fetch(): array|false {
        return $this->lastResult?->fetch() ?? false;
    }

    /** Retorna todas las filas */
    public function fetchAll(int $mode = 0, int $col = 0): array {
        return $this->lastResult?->fetchAll($mode, $col) ?? [];
    }

    /** Retorna el valor de la primera columna */
    public function fetchColumn(int $col = 0): mixed {
        return $this->lastResult?->fetchColumn($col) ?? false;
    }
}

// ─── Factory Singleton ────────────────────────────────────────────────────────
/**
 * Retorna la instancia singleton de TursoPDO.
 * Lee las credenciales desde variables de entorno.
 */
function getTursoDB(): TursoPDO {
    static $instance = null;
    if ($instance === null) {
        $url   = getenv('TURSO_DATABASE_URL') ?: ($_ENV['TURSO_DATABASE_URL'] ?? '');
        $token = getenv('TURSO_AUTH_TOKEN')   ?: ($_ENV['TURSO_AUTH_TOKEN']   ?? '');
        if (!$url || !$token) {
            throw new \RuntimeException('Variables de entorno TURSO_DATABASE_URL y TURSO_AUTH_TOKEN requeridas.');
        }
        $instance = new TursoPDO($url, $token);
    }
    return $instance;
}
