<?php
/**
 * N-gram Search
 *
 * Enabling MySQL's N-gram tokenizer for CJK full-text search. This module is
 * only suitable for MySQL 5.7 or later. If you use MariaDB, please try
 * 'Mroonga search' instead."
 *
 *
 * @copyright Kentaro Fukuchi, 2020
 * @license MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace NgramSearch;

use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Stdlib\Message;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\ResultSetMapping;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $this->manageSettings($serviceLocator->get('Omeka\Settings'), 'install');

        $connection = $serviceLocator->get('Omeka\Connection');

        $this->checkDatabase($connection);

        $sm = $connection->getSchemaManager();
        $fkeyName = $this->getForeignKeyName($sm, 'Installation');
        $idxName = $this->getFulltextKeyName($sm, 'Installation');

        $sql = "ALTER TABLE fulltext_search DROP FOREIGN KEY $fkeyName";
        $result = $connection->executeQuery($sql);

        $sql = "ALTER TABLE fulltext_search DROP KEY $idxName";
        $result = $connection->executeQuery($sql);

        $sql = "ALTER TABLE fulltext_search ADD FULLTEXT KEY $idxName (`title`, `text`) WITH PARSER NGRAM";
        $result = $connection->executeQuery($sql);

        $sql = "ALTER TABLE fulltext_search ADD CONSTRAINT $fkeyName FOREIGN KEY ( `owner_id` ) REFERENCES `user` ( `id` ) ON DELETE SET NULL";
        $result = $connection->executeQuery($sql);

        $messenger = new Messenger();
        $messenger->addWarning("Now re-index fulltext search.");
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $this->manageSettings($serviceLocator->get('Omeka\Settings'), 'uninstall');

        $connection = $serviceLocator->get('Omeka\Connection');
        $sm = $connection->getSchemaManager();
        $fkeyName = $this->getForeignKeyName($sm, 'Uninstallation');
        $idxName = $this->getFulltextKeyName($sm, 'Uninstallation');

        $sql = "ALTER TABLE fulltext_search DROP FOREIGN KEY $fkeyName";
        $result = $connection->executeQuery($sql);

        $sql = "ALTER TABLE fulltext_search DROP KEY $idxName";
        $result = $connection->executeQuery($sql);

        $sql = "ALTER TABLE fulltext_search ADD FULLTEXT KEY $idxName (`title`, `text`)";
        $result = $connection->executeQuery($sql);

        $sql = "ALTER TABLE fulltext_search ADD CONSTRAINT $fkeyName FOREIGN KEY ( `owner_id` ) REFERENCES `user` ( `id` ) ON DELETE SET NULL";
        $result = $connection->executeQuery($sql);

        $messenger = new Messenger();
        $messenger->addWarning("Now re-index fulltext search.");
    }

    protected function manageSettings($settings, $process, $key = 'config')
    {
        $config = require __DIR__ . '/config/module.config.php';
        $defaultSettings = $config[strtolower(__NAMESPACE__)][$key];
        foreach ($defaultSettings as $name => $value) {
            switch ($process) {
            case 'install':
                $settings->set($name, $value);
                break;
            case 'uninstall':
                $settings->delete($name);
                break;
            }
        }
    }

    protected function getForeignKeyName($schemaManager, $process)
    {
        $fkeys = $schemaManager->listTableForeignKeys('fulltext_search');
        if ($fkeys[0]->getLocalColumns()[0] !== 'owner_id') {
            $message = new Message("The table schema of 'fulltext_search' is different from what is expected. %s aborted.", $process);
            throw new ModuleCannotInstallException($message);
        }
        return $fkeys[0]->getName();
    }

    protected function getFulltextKeyName($schemaManager, $process)
    {
        $ikeys = $schemaManager->listTableIndexes('fulltext_search');
        foreach ($ikeys as $ikey) {
            if ($ikey->getColumns() === ['title', 'text']) {
                return $ikey->getName();
            }
        }
        $message = new Message("The table schema of 'fulltext_search' is different from what is expected. %s aborted.", $process);
        throw new ModuleCannotInstallException($message);
    }

    /**
     * Check the database and throws an error if it is not MySQL.
     *
     * @throws ModuleCannotInstallException
     */
    protected function checkDatabase(Connection $connection)
    {
        $serverVersion = $connection->getWrappedConnection()->getServerVersion();
        if(stripos($serverVersion, 'mariadb') !== false) {
            $message = new Message('This module can be used only with *MySQL* 5.7 or later, while this site uses MariaDB. We recommend you to try "Mroonga search" module instead.');
            throw new ModuleCannotInstallException($message);
        }
        if(version_compare($serverVersion, '5.7', '<')) {
            $message = new Message('This module can be used only with *MySQL* 5.7 or later, while this site uses version %s.', $serverVersion);
            throw new ModuleCannotInstallException($message);
        }
    }
}
