<?php
namespace GDO\ImportGWF3\Method;
use GDO\Core\Logger;
use GDO\ImportGWF3\MethodImport;
use GDO\User\GDO_Permission;
use GDO\User\GDO_UserPermission;
use GDO\Usergroup\GDO_Usergroup;
/**
 * @author gizmore
 */
final class ImportPermissions extends MethodImport
{
    public function run()
    {
        Logger::logCron("Importing Permissions");
        
		$perms = $this->step1();
		$uperm = $this->step2();
		
		Logger::logCron("Created $perms Permissions and $uperm associations.");
    }
    
	private function step1()
	{
		$perms = GDO_Permission::table();
		$ugrup = GDO_Usergroup::table();
		$this->gdodb()->disableForeignKeyCheck();
		$perms->truncate();
		$ugrup->truncate();
		$this->gdodb()->enableForeignKeyCheck();
		
		# permissions + usergroup
		$result = $this->gwfdb()->queryRead("SELECT * FROM {$this->prefix}group");
		$permsData = [];
		$ugrupData = [];
		while ($row = mysqli_fetch_assoc($result))
		{
			$permsData[] = $this->permsRow($row);
			if ($ugrupRow = $this->ugrupRow($row))
			{
				$ugrupData[] = $ugrupRow;
			}
		}
		mysqli_free_result($result);
	
		$this->gdodb();
		GDO_Permission::bulkReplace($perms->gdoColumnsCache(), $permsData);
		GDO_Usergroup::bulkReplace($ugrup->gdoColumnsCache(), $ugrupData);
		return count($permsData);
	}
	
	private function step2()
	{
        $uperm = GDO_UserPermission::table();
        $this->gdodb()->disableForeignKeyCheck();
        $uperm->truncate();
        $this->gdodb()->enableForeignKeyCheck();
        
        $result = $this->gwfdb()->queryRead("SELECT * FROM {$this->prefix}usergroup");
        $upermData = [];
        while ($row = mysqli_fetch_assoc($result))
        {
        	if ($upermRow = $this->upermRow($row))
        	{
        		$upermData[] = $upermRow;
        	}
        }
        mysqli_free_result($result);
        
        $this->gdodb();
        GDO_UserPermission::bulkReplace($uperm->gdoColumnsCache(), $upermData);
        
        return count($upermData);
	}
	
    private function permsRow(array $row)
    {
    	return array(
    		$row['group_id'],
    		$row['group_name'],
    	);
    }
    private function ugrupRow(array $row)
    {
    	if ($row['group_founder'])
    	{
	    	return array(
	    		$row['group_id'],
	    		null,
	    		$this->gwflanguage($row['group_lang'], 'en'),
	    		$this->gwfcountry($row['group_country']),
	    		$this->gwfdate($row['group_date'], $this->gwfdatenow()),
	    		$row['group_founder'],
	    		$this->ugrupView($row),
	    		$this->ugrupJoin($row),
	    	);
    	}
    }
    
    private function ugrupView(array $row)
    {
    	if ($row['group_options'] & 0x100) return GDO_Usergroup::VIEW_PUBLIC;
    	if ($row['group_options'] & 0x200) return GDO_Usergroup::VIEW_USER;
    	if ($row['group_options'] & 0x400) return GDO_Usergroup::VIEW_MEMBER;
    	if ($row['group_options'] & 0x800) return GDO_Usergroup::VIEW_INVISBLE;
    }
    private function ugrupJoin(array $row)
    {
    	if ($row['group_options'] & 0x01) return GDO_Usergroup::JOIN_FULL;
    	if ($row['group_options'] & 0x02) return GDO_Usergroup::JOIN_INVITE;
    	if ($row['group_options'] & 0x04) return GDO_Usergroup::JOIN_MODERATE;
    	if ($row['group_options'] & 0x08) return GDO_Usergroup::JOIN_FREE;
    }
    
    private function upermRow(array $row)
    {
    	if ( ($row['ug_userid'] > 0) && ($row['ug_groupid'] > 0) )
    	{
	    	return array(
	    		$row['ug_userid'],
	    		$row['ug_groupid'],
	    		$this->gwfdatenow(),
	    		$this->systemID(),
	    	);
    	}
    }
    
}
