<?php
declare(strict_types = 1);
namespace System\Component\Dev;

use System\Injectable;
use System\Helper\File;

/**
 * Class HandleModels
 *
 *
 * @package System\Component
 * @author renato gabriel
 * @since 24/01/2020
 * @version 1.0
 */
class HandleModels extends Injectable
{

    protected $modelOptions = [
        '--force'
    ];

    protected $abstractOptions = [
        '--doc',
        'relations',
        'fk',
        'annotate',
        'force',
        'get-set',
        'abstract',
        'force'
    ];

    protected $sourceConfFile = PATH_CONFIG . DS . 'config_webtools.php';

    /**
     *
     * @todo mudar ao mover por model, criar com o abstrato no model ja
     *      
     * @var string
     */
    protected $extends = '\\\System\\\Mvc\\\Model';

    protected $data;

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
    public function __construct(bool $abstract = false, bool $clear = false, array $skip = [], array $project = [
        'vztus-cms'
    ])
    {
        $sql = "SELECT nspname as schema FROM pg_catalog.pg_namespace WHERE nspname NOT IN('pg_toast','pg_temp_1','pg_toast_temp_1','pg_catalog','public','information_schema')";
        $fetchSchemas = $this->db->fetchAll($sql);
        $commands = [
            'cd /var/www/html/' . $project[0]
        ];

        foreach ($fetchSchemas as $row) {
            $schema = $row['schema'];
            $schemaUc = ucfirst($row['schema']);

            $configFile = realpath($this->sourceConfFile);

            if (true === $abstract) {
                $dir = realpath(PATH_MODELS . DS . 'Abstracts' . DS . $schemaUc);
                $nameSpace = sprintf('App\\\Models\\\Abstracts\\\%s', $schemaUc);
                if (! realpath(PATH_MODELS . DS . 'Abstracts')) {
                    File::criarDir(PATH_MODELS . DS . 'Abstracts');
                }
                if (! $dir) {
                    File::criarDir(PATH_MODELS . DS . 'Abstracts' . DS . $schemaUc);
                    $dir = realpath(PATH_MODELS . DS . 'Abstracts' . DS . $schemaUc);
                }
            } else {
                $dir = realpath(PATH_MODELS . DS . $schemaUc);
                $nameSpace = sprintf('App\\\Models\\\%s', $schemaUc);
                if (! $dir) {
                    File::criarDir(PATH_MODELS . DS . $schemaUc);
                    $dir = realpath(PATH_MODELS . DS . $schemaUc);
                }
            }

            if (true === $clear and ! in_array($schema, $skip)) {
                $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
                $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($ri as $file) {
                    if ($file->getFilename() === '.gitignore' or $file->isDir()) {
                        continue;
                    }
                    unlink($file);
                }
            } else if (in_array($schema, $skip)) {
                continue;
            }

            $commands[] = sprintf('phalcon all-models --schema=%s --namespace=%s --extends=%s --output=%s --config=%s %s 2>&1', $schema, $nameSpace, $this->extends, $dir, $configFile, join(' --', (true === $abstract) ? $this->abstractOptions : $this->modelOptions));
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
}
