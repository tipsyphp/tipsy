<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Base Resource class providing Active Record pattern for database operations
 * 
 * Features:
 * - Automatic table schema discovery and management
 * - CRUD operations with type conversion
 * - Field validation and table creation
 * - Factory pattern integration
 * - Database abstraction for MySQL, PostgreSQL, and SQLite
 */
class Resource extends Model
{
    /** @var ?string Database table name */
    private ?string $_table = null;
    
    /** @var ?string Primary key field name */
    private ?string $_id_var = null;
    
    /** @var ?array<string, object> Field definitions from database */
    private ?array $_fields = null;
    
    /** @var ?Db Database connection */
    private ?Db $_db = null;
    
    /** @var ?App Framework instance */
    private ?App $_tipsy = null;
    
    /** @var array<string, mixed> Base configuration */
    private array $_baseConfig = [];
    
    /** @var ?string Associated service name */
    private ?string $_service = null;

    /**
     * @param array<string, mixed> $args Resource configuration and initial data
     */
    public function __construct(array $args = [])
    {
        parent::__construct();
        
        $this->_baseConfig = $args;

        // Extract framework-specific configuration
        if (isset($args['_tipsy'])) {
            $this->_tipsy = $args['_tipsy'];
            unset($args['_tipsy']);
        }
        
        if (isset($args['_service'])) {
            $this->_service = $args['_service'];
            unset($args['_service']);
        }
        
        if (isset($args['_id'])) {
            $this->idVar($args['_id']);
            unset($args['_id']);
        }
        
        if (isset($args['_table'])) {
            $this->table($args['_table']);
            unset($args['_table']);
        }
        
        if (isset($args['_fields'])) {
            $this->__fields = $args['_fields'];
            unset($args['_fields']);
        }

        // Load remaining args as object data
        if ($args) {
            $this->load($args);
        }
    }

    /**
     * Retrieve field list from database with automatic schema discovery
     * 
     * @return array<string, object>
     */
    public function fields(): array
    {
        if ($fields = $this->db()->fields($this->table())) {
            $this->_fields = $fields;
            return $this->_fields;
        }

        $fields = [];
        
        // Get column information based on database driver
        $query = match ($this->db()->driver()) {
            'mysql' => 'SHOW COLUMNS FROM `' . $this->table() . '`',
            'sqlite' => 'PRAGMA table_info("' . $this->table() . '")',
            'pgsql' => 'SELECT column_name as Field, data_type as Type, is_nullable as Null, column_default as Default FROM information_schema.columns WHERE table_name = \'' . $this->table() . '\'',
            default => throw new Exception('Unsupported database driver: ' . $this->db()->driver())
        };

        try {
            $rows = $this->db()->get($query);
            
            if (!count($rows)) {
                $this->createTable();
            } else {
                $fields = $this->parseFieldDefinitions($rows);
            }
        } catch (\Exception $e) {
            // Table doesn't exist - create it if we can
            $this->createTable();
        }

        $this->_fields = $fields;
        $this->db()->fields($this->table(), $fields);
        
        return $this->_fields;
    }

    /**
     * Parse database field definitions into normalized format
     * 
     * @param array<object> $rows Raw database column information
     * @return array<string, object>
     */
    private function parseFieldDefinitions(array $rows): array
    {
        $fields = [];
        
        foreach ($rows as $row) {
            $field = match ($this->db()->driver()) {
                'sqlite' => (object) [
                    'field' => $row->name,
                    'type' => $row->type,
                    'null' => !$row->notnull
                ],
                'mysql' => $this->parseMysqlField($row),
                'pgsql' => $this->parsePostgresField($row),
                default => throw new Exception('Unsupported database driver')
            };
            
            $fields[$field->field] = $field;
        }
        
        return $fields;
    }

    /**
     * Parse MySQL field definition
     */
    private function parseMysqlField(object $row): object
    {
        $field = (object) [
            'field' => $row->Field,
            'type' => $row->Type,
            'null' => $row->Null === 'YES',
            'auto' => $row->Extra === 'auto_increment'
        ];

        // Normalize type names
        if (in_array($field->type, ['tinyint(1)', 'tinyint(1) unsigned'])) {
            $field->type = 'boolean';
        } elseif (str_contains($field->type, 'int')) {
            $field->type = 'int';
        }

        return $field;
    }

    /**
     * Parse PostgreSQL field definition
     */
    private function parsePostgresField(object $row): object
    {
        $field = (object) [
            'field' => $row->field,
            'type' => $row->type,
            'null' => $row->null === 'YES',
            'auto' => $row->default && preg_match('/^nextval\(/', $row->default)
        ];

        // Normalize type names
        if ($field->type === 'integer') {
            $field->type = 'int';
        }

        // Extract sequence name for auto-increment fields
        if ($field->auto && $field->field === $this->idVar()) {
            $field->sequence = preg_replace('/^nextval\(\'(.*)\'.*\)$/i', '\\1', $row->default);
        }

        return $field;
    }

    /**
     * Create database table based on field definitions
     * 
     * @throws Exception When table cannot be created
     */
    public function createTable(): void
    {
        if (!isset($this->__fields)) {
            throw new Exception('Could not create table "' . $this->table() . '"');
        }

        $query = 'CREATE TABLE `' . $this->table() . '` (';
        $fieldDefinitions = [];

        foreach ($this->__fields as $name => $field) {
            $fieldDefinitions[] = $this->buildFieldDefinition($name, $field);
        }

        $query .= implode(', ', $fieldDefinitions);
        $query .= ', PRIMARY KEY (`' . $this->idVar() . '`))';

        $this->db()->query($query);
    }

    /**
     * Build SQL field definition for table creation
     */
    private function buildFieldDefinition(string $name, object $field): string
    {
        $definition = "`{$name}` ";
        
        $default = match (true) {
            $field->default === null => 'NULL',
            $field->default === true => 'true',
            $field->default === false => 'false',
            is_int($field->default) => (string) $field->default,
            default => "'{$field->default}'"
        };

        $definition .= match ($field->type) {
            'int' => $this->buildIntFieldDefinition($field, $default),
            'char' => $this->buildCharFieldDefinition($field, $default),
            'bool' => $this->buildBoolFieldDefinition($field, $default),
            default => $field->name ?? 'TEXT'
        };

        return $definition;
    }

    /**
     * Build integer field definition
     */
    private function buildIntFieldDefinition(object $field, string $default): string
    {
        if ($this->db()->driver() === 'pgsql') {
            return ($field->auto ? 'serial' : 'integer') . 
                   (!$field->null ? ' NOT NULL' : '');
        }
        
        $length = $field->length ?? 11;
        $unsigned = $field->unsigned ? ' unsigned' : '';
        $null = !$field->null ? ' NOT NULL' : '';
        $auto = $field->auto ? ' AUTO_INCREMENT' : '';
        $defaultValue = $field->default ? " DEFAULT {$default}" : '';
        
        return "int({$length}){$unsigned}{$null}{$auto}{$defaultValue}";
    }

    /**
     * Build character field definition
     */
    private function buildCharFieldDefinition(object $field, string $default): string
    {
        $length = $field->length ?? 255;
        $null = !$field->null ? ' NOT NULL' : '';
        $defaultValue = $field->default ? " DEFAULT {$default}" : '';
        
        return "varchar({$length}){$null}{$defaultValue}";
    }

    /**
     * Build boolean field definition
     */
    private function buildBoolFieldDefinition(object $field, string $default): string
    {
        $type = $this->db()->driver() === 'pgsql' ? 'bool' : 'tinyint(1)';
        $defaultValue = $field->default ? " DEFAULT {$default}" : '';
        
        return "{$type} NOT NULL{$defaultValue}";
    }

    /**
     * Drop the database table
     */
    public function dropTable(): void
    {
        $this->db()->query('DROP TABLE IF EXISTS `' . $this->table() . '`');
    }

    /**
     * Static factory method for creating new resources
     * 
     * @param array<string, mixed> $args Resource data
     */
    public static function __create_static(array $args = []): static
    {
        $args['_tipsy'] = Tipsy::app();
        $obj = new static($args);
        $obj->save();
        return $obj;
    }

    /**
     * Instance method for creating new resources
     * 
     * @param array<string, mixed> $args Resource data
     */
    public function __create(array $args = []): static
    {
        $object = clone $this;
        $object->tipsy($this->tipsy());
        $object->load($args);
        $object->save(true);
        return $object;
    }

    /**
     * Get database ID value
     */
    public function dbId(): mixed
    {
        return $this->{$this->idVar()};
    }

    /**
     * Load object with data from database or array
     * 
     * @param mixed $id Object, array, or ID to load
     */
    public function load(mixed $id = null): self
    {
        // Initialize with field defaults
        $fields = $this->fields();
        foreach ($fields as $field) {
            $this->{$field->field} = $this->{$field->field} ?? '';
        }

        // Use existing ID if no parameter provided
        if (!$id && $this->dbId()) {
            $id = $this->dbId();
        }

        $data = match (true) {
            is_object($id) => $id,
            is_array($id) => (object) $id,
            $id !== null => $this->loadFromDatabase($id),
            default => new Model()
        };

        // Copy properties from loaded data
        if ($data) {
            foreach (get_object_vars($data) as $property => $value) {
                $this->$property = $value;
            }
        }

        // Type conversion based on field definitions
        $this->convertFieldTypes();

        // Register with factory if enabled
        if ($this->tipsy() && $this->tipsy()->config()['tipsy']['factory'] !== false) {
            $this->tipsy()->factory($this);
        }

        return $this;
    }

    /**
     * Load data from database by ID
     */
    private function loadFromDatabase(mixed $id): ?object
    {
        $result = $this->db()->get(
            'SELECT * FROM `' . $this->table() . '` WHERE `' . $this->idVar() . '` = ? LIMIT 1',
            [$id]
        );
        
        return $result[0] ?? null;
    }

    /**
     * Convert field values to appropriate PHP types
     */
    private function convertFieldTypes(): void
    {
        foreach ($this->fields() as $field) {
            match ($field->type) {
                'int' => $this->{$field->field} = (int) $this->{$field->field},
                'boolean' => $this->{$field->field} = (bool) $this->{$field->field},
                default => null
            };
        }
    }

    /**
     * Save object to database (insert or update)
     */
    public function save(?bool $insert = null): self
    {
        $insert = $insert ?? (!$this->dbId());
        $fields = $this->fields();
        
        if ($insert) {
            return $this->performInsert($fields);
        }
        
        return $this->performUpdate($fields);
    }

    /**
     * Perform database INSERT operation
     * 
     * @param array<string, object> $fields Field definitions
     */
    private function performInsert(array $fields): self
    {
        $insertFields = [];
        $placeholders = [];
        $values = [];
        
        foreach ($fields as $field) {
            // Skip auto-increment primary key
            if ($field->auto && $field->field === $this->idVar()) {
                continue;
            }
            
            $value = $this->prepareFieldValue($field);
            $insertFields[] = "`{$field->field}`";
            $placeholders[] = ":{$field->field}";
            $values[$field->field] = $value;
        }
        
        $query = 'INSERT INTO `' . $this->table() . '` (' . 
                 implode(', ', $insertFields) . ') VALUES (' . 
                 implode(', ', $placeholders) . ')';
        
        $this->db()->query($query, $values);
        
        // Set auto-generated ID
        if (isset($fields[$this->idVar()]) && $fields[$this->idVar()]->auto) {
            $sequence = $fields[$this->idVar()]->sequence ?? null;
            $this->{$this->idVar()} = $this->db()->db()->lastInsertId($sequence);
        }
        
        return $this;
    }

    /**
     * Perform database UPDATE operation
     * 
     * @param array<string, object> $fields Field definitions
     */
    private function performUpdate(array $fields): self
    {
        $setFields = [];
        $values = [];
        
        foreach ($fields as $field) {
            // Skip primary key in SET clause
            if ($field->field === $this->idVar()) {
                continue;
            }
            
            $value = $this->prepareFieldValue($field);
            $setFields[] = "`{$field->field}` = :{$field->field}";
            $values[$field->field] = $value;
        }
        
        $values['id'] = $this->{$this->idVar()};
        
        $query = 'UPDATE `' . $this->table() . '` SET ' . 
                 implode(', ', $setFields) . 
                 ' WHERE `' . $this->idVar() . '` = :id';
        
        $this->db()->query($query, $values);
        
        return $this;
    }

    /**
     * Prepare field value for database storage
     */
    private function prepareFieldValue(object $field): mixed
    {
        $value = $this->{$field->field};
        
        // Handle null values
        if ($value === '' && $field->null) {
            return null;
        } elseif ($value === null && !$field->null) {
            return '';
        }
        
        // Type-specific preparation
        return match ($field->type) {
            'boolean' => $this->prepareBooleanValue($value),
            'int' => (int) $value,
            'datetime' => $value === '0000-00-00 00:00:00' && $field->null ? null : $value,
            'date' => $value === '0000-00-00' && $field->null ? null : $value,
            default => $value
        };
    }

    /**
     * Prepare boolean value for database storage
     */
    private function prepareBooleanValue(mixed $value): mixed
    {
        $boolValue = (bool) $value;
        
        // MySQL stores booleans as tinyint
        if ($this->db()->driver() === 'mysql') {
            return $boolValue ? 1 : 0;
        }
        
        return $boolValue;
    }

    /**
     * Delete record from database
     * 
     * @throws Exception When no ID is available for deletion
     */
    public function delete(): self
    {
        if (!$this->dbId()) {
            throw new Exception('Cannot delete. No ID was given.');
        }
        
        $this->db()->query(
            'DELETE FROM `' . $this->table() . '` WHERE `' . $this->idVar() . '` = ?',
            [$this->dbId()]
        );
        
        return $this;
    }

    /**
     * Remove properties not in database fields
     */
    public function strip(): self
    {
        $fieldNames = array_keys($this->fields());
        $objectVars = get_object_vars($this);
        
        foreach ($objectVars as $property => $value) {
            if (!in_array($property, $fieldNames) && !str_starts_with($property, '_')) {
                unset($this->$property);
            }
        }
        
        return $this;
    }

    /**
     * Serialize array data into object properties
     * 
     * @param array<string, mixed>|object $data Data to serialize
     */
    public function serialize(array|object $data): self
    {
        if (!is_array($data)) {
            $data = get_object_vars($data);
        }
        
        $properties = $this->properties();
        
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $properties)) {
                $this->$key = $value;
            }
        }
        
        return $this;
    }

    /**
     * Get/set ID variable name
     */
    public function idVar(?string $idVar = null): ?string
    {
        if ($idVar !== null) {
            $this->_id_var = $idVar;
            return $this;
        }
        
        return $this->_id_var;
    }

    /**
     * Get/set table name
     */
    public function table(?string $table = null): ?string
    {
        if ($table !== null) {
            $this->_table = $table;
            return $this;
        }
        
        return $this->_table;
    }

    /**
     * Execute database query and return Looper of results
     */
    public function __query(string $query, mixed ...$args): Looper
    {
        // Handle array of parameters vs individual parameters
        $parameters = (count($args) === 1 && is_array($args[0])) ? $args[0] : $args;
        
        $result = $this->db()->query($query, $parameters);
        $items = [];
        
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            if ($this->tipsy()->services($this->service())) {
                $items[] = $this->tipsy()->service($this->service(), $row);
            } elseif (class_exists($this->service())) {
                $className = $this->service();
                $items[] = new $className($row);
            } else {
                $items[] = $row;
            }
        }
        
        return new Looper($items);
    }

    /**
     * Static query method
     */
    public static function __query_static(string $query, mixed ...$args): Looper
    {
        $instance = new static();
        $instance->tipsy(Tipsy::app());
        $instance->service(static::class);
        
        return $instance->__query($query, ...$args);
    }

    /**
     * Shorthand for __query
     */
    public function __q(string $query, mixed ...$args): Looper
    {
        return $this->__query($query, ...$args);
    }

    /**
     * Static shorthand for __query_static
     */
    public static function __q_static(string $query, mixed ...$args): Looper
    {
        return static::__query_static($query, ...$args);
    }

    /**
     * Get/set framework instance
     */
    public function tipsy(?App $tipsy = null): ?App
    {
        if ($tipsy !== null) {
            $this->_tipsy = $tipsy;
        }
        
        return $this->_tipsy;
    }

    /**
     * Get database connection
     */
    public function db(): ?Db
    {
        if ($this->tipsy()?->db()) {
            return $this->tipsy()->db();
        }
        
        return Tipsy::db();
    }

    /**
     * Get/set service name
     */
    public function service(?string $service = null): ?string
    {
        if ($service !== null) {
            $this->_service = $service;
        }
        
        return $this->_service;
    }
}
