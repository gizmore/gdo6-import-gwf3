<?php
namespace GDO\ImportGWF3\Method;
use GDO\Core\Logger;
use GDO\ImportGWF3\MethodImport;
/**
 * @author gizmore
 */
final class ImportPermissions extends MethodImport
{
    public function run()
    {
        Logger::logCron("Importing Permissions");
        
        $this->gwfdb()->queryRead("SELECT * FROM {$this->prefix}group");
        
        
    }
    
}
