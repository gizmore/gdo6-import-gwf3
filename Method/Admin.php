<?php
namespace GDO\ImportGWF3\Method;
use GDO\Core\MethodAdmin;
use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_AntiCSRF;
use GDO\DB\Cache;
use GDO\DB\Database;
use GDO\ImportGWF3\Module_ImportGWF3;
use GDO\Core\GDT_Success;
use GDO\Core\GDT_Response;
use GDO\UI\GDT_HTML;

final class Admin extends MethodForm
{
    use MethodAdmin;
    public function getPermission() {}
    public function isUserRequired() { return false; }
    public function isTransactional() { return false; }
    
    ############
    ### Form ###
    ############
    public function execute()
    {
        # Normal form but prepend admin navbar
        return $this->renderNavBar()->addField(parent::execute());
    }

    public function createForm(GDT_Form $form)
    {
        $form->addFields(array(
            GDT_AntiCSRF::make(),
        ));
        $form->actions()->addField(GDT_Submit::make('btn_import_gwf3'));
    }
    
    ##############
    ### Import ###
    ##############
    public function onSubmit_btn_import_gwf3(GDT_Form $form)
    {
        # Try gwf3 conn
        if (!$this->connectGWFDB())
        {
            return $this->error('err_connect_gwf3_db')->addField($this->renderPage());
        }
        
        # Call importers
        ob_start();
        $this->onImportAll();
        $content = ob_get_contents();
        ob_end_clean();

        # Done
        return GDT_Response::makeWith(GDT_Success::make()->addField(GDT_HTML::withHTML($content)))->
            addField($this->message('msg_gwf3_import_finished'))->
            addField($this->renderPage());
    }
    
    #################
    ### DB-Switch ###
    #################
    private $gdodb;
    private $gwfdb;
    private $prefix;
    public function gdodb() { Database::$INSTANCE = $this->gdodb; return $this->gdodb; }
    public function gwfdb() { Database::$INSTANCE = $this->gwfdb; return $this->gwfdb; }
    public function connectGWFDB()
    {
        # Remember GDO
        $this->gdodb = Database::$INSTANCE;
        # Create GWF3
        $module = Module_ImportGWF3::instance();
        $this->gwfdb = new Database($module->cfgDBHost(), $module->cfgDBUser(), $module->cfgDBPass(), $module->cfgDBName());
        $result = !!$this->gwfdb->connect();
        $this->prefix = $module->cfgDBPrefix();
        # Restore GDO
        $this->gdodb();
        return $result;
    }
    
    ##############
    ### Import ###
    ##############
    public function onImportAll()
    {
        try
        {
//             ImportUsers::make()->import($this->gwfdb);
//             ImportPermissions::make()->import($this->gwfdb);
//             ImportAvatars::make()->import($this->gwfdb);
            ImportForum::make()->import($this->gwfdb);
        }
        finally
        {
            $this->gdodb();
            Cache::flush();
        }
    }

}