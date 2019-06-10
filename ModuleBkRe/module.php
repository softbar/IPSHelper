<?php
/**
 * @author Xavier
 *
 */
class ModuleBackupRestore extends IPSModule {
	/**
	 * {@inheritDoc}
	 * @see IPSModule::Create()
	 */
	public function Create(){
		parent::Create();
		$this->RegisterPropertyInteger('Mode', 0);
		$this->RegisterPropertyBoolean('IncludeName', true);
		$this->RegisterPropertyBoolean('CreateModule', true);
		$this->RegisterPropertyString('Data', '');
	}
	/**
	 * {@inheritDoc}
	 * @see IPSModule::ApplyChanges()
	 */
	public function ApplyChanges(){
		parent::ApplyChanges();
  	}
	
  	/**
  	 * {@inheritDoc}
  	 * @see IPSModule::GetConfigurationForm()
  	 */
  	public function GetConfigurationForm() {
  		$ver=(int)IPS_GetKernelVersion();
  		$f=json_decode(file_get_contents(__DIR__.'/form.json'),true);
  		$f['actions'][]=["name"=> "instance", "type"=> "SelectInstance", "caption"=> "Module InstanceID" ];
  		if(($mode=$this->ReadPropertyInteger('Mode'))==1){
  			$f['elements'][]=["name"=>"CreateModule","type"=>"CheckBox", "caption"=>"Create Instance if not selected"];
  			$f['elements'][]=["name"=> "IncludeName","type"=> "CheckBox",  "caption"=> "Restore instance name"];
  			$code="";
  			if(!$this->ReadPropertyBoolean('CreateModule'))
  				$code="if(!\$instance)echo 'please select instance first';else";
  			$code.="MBKRE_RestoreModuleConfiguration($this->InstanceID,(int)\$instance);";
  				
  		}else {
  			$code="if(!\$instance)echo 'please select instance first';else MBKRE_BackupModuleConfiguration($this->InstanceID,(int)\$instance);";
  		}
  		
        $f['actions'][]=["type"=>"Button", $ver<5?"label":"caption"=>$mode?"Execute restore":"Execute backup", "onClick"=>$code];
  		
  		
  		$bkname='-';
  		if($d=$this->restore_backup()){
  			$bkmodule=$d['ModuleInfo']['ModuleName'];
  			$bkname=$d['name'];
  		} else $bkmodule=$this->Translate('empty');
  		
  		$f['elements'][]=["type"=>"Label", "caption"=>$this->Translate("Current backup class")." : $bkmodule"];	
  		$f['elements'][]=["type"=>"Label", "caption"=>$this->Translate("Backup instance name")." : $bkname"];	
   		
 		
   
  		
	  		
  		return json_encode($f);
  	}
	
	/**
	 * @param int $ModuleInstanceID InstanzeID zum sichern
	 * @throws Exception
	 * @return boolean True wenn kein Fehler aufgetreten
	 */
	public function BackupModuleConfiguration(int $ModuleInstanceID){
		if(IPS_InstanceExists($ModuleInstanceID)){
			$Data = IPS_GetInstance($ModuleInstanceID);
			$Data['name']=IPS_GetName($ModuleInstanceID);
			$Data['@config'] = json_decode(IPS_GetConfiguration($ModuleInstanceID),true);
			$this->store_backup($Data);
			return true;
		} else throw new Exception(sprintf($this->Translate("Instance %s not found"),$ModuleInstanceID),E_USER_ERROR);
	}
	/**
	 * @param int $NewModuleInstanceID InstanzeID zum zurücksichern oder 0 wenn automatische Erstellung aktiviert ist
	 * @throws Exception
	 * @return boolean True wenn kein Fehler aufgetreten
	 */
	public function RestoreModuleConfiguration(int $NewModuleInstanceID){
		if(IPS_InstanceExists($NewModuleInstanceID) || $this->ReadPropertyBoolean('CreateModule')){
			if(!$restore=$this->restore_backup()){
				throw new Exception($this->Translate("No backup found"),E_USER_ERROR);
			}
			if($NewModuleInstanceID==0||!IPS_InstanceExists($NewModuleInstanceID)){
				$NewModuleInstanceID=IPS_CreateInstance($restore['ModuleInfo']['ModuleID']);
			}
			
			$newInstance=IPS_GetInstance($NewModuleInstanceID);
			if($newInstance['ModuleInfo']['ModuleID']!=$restore['ModuleInfo']['ModuleID']){
				throw new Exception($this->Translate("Restore module mismatch. Require").' '.$restore['ModuleInfo']['ModuleName'],E_USER_ERROR);
			}
			$changed=false;
			if($data=json_decode(IPS_GetConfiguration($NewModuleInstanceID),true)){
				foreach(array_keys($data) as $k){
					if(isset($restore['@config'][$k])){
						if($data[$k]!=$restore['@config'][$k])$changed=true;
						$data[$k]=$restore['@config'][$k];
					}
				}
			}
			if($changed){
				IPS_SetConfiguration($NewModuleInstanceID,json_encode($data));
				IPS_ApplyChanges($NewModuleInstanceID);
			}
			if($this->ReadPropertyBoolean('IncludeName'))
				IPS_SetName($NewModuleInstanceID, $restore['name']);
			return true;
		} else throw new Exception(sprintf($this->Translate("Instance %s not found"),$NewModuleInstanceID),E_USER_ERROR);
		
	}
	
	private function store_backup($Data){
		IPS_SetProperty($this->InstanceID, 'Data', json_encode($Data));
		IPS_ApplyChanges($this->InstanceID);
//		$this->SetBuffer('BACKUP_DATA', json_encode($Data));
		
	}
	private function restore_backup(){
		return json_decode($this->ReadPropertyString('Data'),true);
// 		return json_decode($this->GetBuffer('BACKUP_DATA'),true);
	}
	
	
}
?>