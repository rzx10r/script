<?php
declare(strict_types = 1);
namespace System\Component\Dev;

use System\Di\Injectable;
use Phalcon\Text;

/**
 * Class HandleModels
 *
 *
 * @package System\Component\Dev
 * @author renato gabriel
 */
class HandleModels extends Injectable
{

    protected array $modelOptions = [
        '--force'
    ];

    protected array $abstractOptions = [
        '--doc',
        // 'relations',
        // 'fk',
        'annotate',
        'force',
        'get-set',
        'abstract',
        'force'
    ];

    /**
     *
     * @var string
     */
    protected string $sourceConfFile = PATH_CONFIG . DS . 'config_webtools.php';

    /**
     *
     * @todo mudar ao mover por model, criar com o abstrato no model ja
     *      
     * @var string
     */
    protected string $extends = '\\\System\\\Mvc\\\Model';

    protected $data;

    /**
     *
     * @var boolean
     */
    protected $extendAbstract = true;

    /**
     *
     * @todo move to phalcon cli
     * @todo do it for each model in each schema instead of each schema.
     * @todo for all projects if $project is null
     * @todo choose schemas.
     *      
     * @param bool $abstract
     * @param bool $clear
     * @param array $skip
     * @param array $project
     * @return array
     */
    public function __construct(bool $abstract = false, bool $clear = false, array $skip = [], array $schemas = [])
    {
        $sql = "SELECT table_schema as schema,table_name as table, table_type as type FROM information_schema.tables ORDER BY table_schema,table_name;";
        $fetchSchemas = $this->db->fetchAll($sql);
        $projectDir = '/var/www/html/webtools';
        $modelDir = realpath($projectDir);
        $commands = [
            'cd ' . $projectDir
        ];
        foreach ($fetchSchemas as $row) {

            $schema = $row['schema'];

            if (($schema === 'information_schema' || $schema === 'pg_catalog') || (count($schemas) && ! in_array($schema, $schemas))) {
                continue;
            }

            $isView = ($row['type'] === 'VIEW') ? true : false;
            $schemaUc = Text::camelize($row['schema']);
            $table = Text::camelize($row['table']);

            $configFile = realpath($this->sourceConfFile);

            if (true === $abstract) {
                $dir = realpath($modelDir . DS . 'Abstracts' . DS . $schemaUc);
                $nameSpace = sprintf('App\\\Models\\\Abstracts\\\%s', $schemaUc);
                if (! realpath($modelDir . DS . 'Abstracts')) {
                    $this->tag->criarDir($modelDir . DS . 'Abstracts');
                }
                if (! $dir) {
                    $this->tag->criarDir($modelDir . DS . 'Abstracts' . DS . $schemaUc);
                    $dir = realpath($modelDir . DS . 'Abstracts' . DS . $schemaUc);
                }
            } else {
                $dir = realpath($modelDir . DS . $schemaUc);
                $nameSpace = sprintf('App\\\Models\\\%s', $schemaUc);
                if ($this->isExtendAbstract()) {
                    $this->extends = sprintf('\\\App\\\Models\\\Abstracts\\\%s\\\Abstract%s', $schemaUc, $table);
                }

                if (! $dir) {
                    $this->tag->criarDir($modelDir . DS . $schemaUc);
                    $dir = realpath($modelDir . DS . $schemaUc);
                }
            }

            if (true === $isView) {
                if (true === $abstract) {
                    $dir = realpath($modelDir . DS . 'Abstracts' . DS . $schemaUc . DS . 'View');
                    $nameSpace = sprintf('App\\\Models\\\Abstracts\\\%s\\\View', $schemaUc);
                    if (! $dir) {
                        $this->tag->criarDir($modelDir . DS . 'Abstracts' . DS . $schemaUc . DS . 'View');
                        $dir = realpath($modelDir . DS . 'Abstracts' . DS . $schemaUc . DS . 'View');
                    }
                } else {
                    $dir = realpath($modelDir . DS . $schemaUc . DS . 'View');
                    $nameSpace = sprintf('App\\\Models\\\%s\\\View', $schemaUc);
                    $this->extends = sprintf('\\\App\\\Models\\\Abstracts\\\%s\\\View\\\Abstract%s', $schemaUc, $table);

                    if (! $dir) {
                        $this->tag->criarDir($modelDir . DS . $schemaUc . DS . 'View');
                        $dir = realpath($modelDir . DS . $schemaUc . DS . 'View');
                    }
                }
            }

            if (true === $clear && ! in_array($schema, $skip)) {
                $this->tag->clearDir($dir);
            } elseif (in_array($schema, $skip)) {
                continue;
            }

            $commands[] = sprintf('phalcon model --name=%s --schema=%s --namespace=%s --extends=%s --output=%s --config=%s %s 2>&1', $row['table'], $schema, $nameSpace, $this->extends, $dir, $configFile, join(' --', (true === $abstract) ? $this->abstractOptions : $this->modelOptions));
        }

        $verbose = null;
        $returnValue = null;
        exec(join(' && ', $commands), $verbose, $returnValue);
        $verbose = array_filter($verbose);
        $return = [
            'return' => $returnValue,
            'command' => $commands,
            'log' => $verbose
        ];
        $this->data = $return;
    }

    public function getResponse()
    {
        return $this->data;
    }

    /**
     *
     * @return boolean
     */
    public function isExtendAbstract(): bool
    {
        return $this->extendAbstract;
    }

    /**
     *
     * @param boolean $extendAbstract
     */
    public function setExtendAbstract(bool $extendAbstract): self
    {
        $this->extendAbstract = $extendAbstract;
        return $this;
    }
}
