<?php
namespace GDO\ImportGWF3;
use GDO\Core\GDO_Module;
use GDO\Net\GDT_Hostname;
use GDO\Type\GDT_Secret;
use GDO\File\GDT_Path;
/**
 * Import gwf3 tables into this gdo6 installation.
 * 
 * @author gizmore
 * @since 6.0.3
 * @version 6.0.3
 */
final class Module_ImportGWF3 extends GDO_Module
{
    public function defaultEnabled() { return false; }
    
    public function href_administrate_module() { return href('ImportGWF3', 'Admin'); }
    
    ##############
    ### Config ###
    ##############
    public function getConfig()
    {
        return array(
            GDT_Hostname::make('gwf3_db_host')->initial('localhost'),
            GDT_Secret::make('gwf3_db_user')->initial('wechall5'),
            GDT_Secret::make('gwf3_db_pass')->initial('wechall5'),
            GDT_Secret::make('gwf3_db_name')->initial('wechall5'),
            GDT_Secret::make('gwf3_db_prefix')->initial('wc4_'),
            GDT_Path::make('gwf3_dbimg_dir')->existingDir(),
        );
    }
    public function cfgDBHost() { return $this->getConfigVar('gwf3_db_host'); }
    public function cfgDBUser() { return $this->getConfigVar('gwf3_db_user'); }
    public function cfgDBPass() { return $this->getConfigVar('gwf3_db_pass'); }
    public function cfgDBName() { return $this->getConfigVar('gwf3_db_name'); }
    public function cfgDBPrefix() { return $this->getConfigVar('gwf3_db_prefix'); }
    public function cfgGWF3Path() { return $this->getConfigVar('gwf3_dbimg_dir'); }
}
