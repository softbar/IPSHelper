<?php
/**
 * @author Xavier
 *
 */
define('IPS_VERSION',IPS_GetKernelVersion());

if(IPS_VERSION<4.3){
	trait Translate {
		private $langcache=null;
		public function Translate($Text){
			return $Text;
		}
	
	}
}else{
	trait Translate {
		
	}
	
}
class ModuleBackupRestore extends IPSModule {
	use Translate;
	private $MaxBackups = 5;
	
	private function fixFormVersion(&$form, $Version=IPS_VERSION){
		if($Version >5.0)return;
		if($Version==5.0)return;

		$fixCaption=function(&$e)use($Version){
			if($Version<5 && isset($e['caption'])){
				$e['label']=$e['caption'];
				unset($e['caption']);
			}
		};
		if($Version<5){
			for($j=0;$j<count($form['elements']);$j++){
				$element=&$form['elements'][$j];
				if($element['type']=='RowLayout'){
					array_splice($form['elements'], $j,null,$element['items']);
				}elseif($element['type']=='PopupButton'){
					array_splice($form['elements'], $j,null,$element['items']);
				}
			}
		}
		if(!empty($form['elements']))foreach($form['elements'] as &$element){
			if($element['type']=='Button')$fixCaption($element);
			if($element['type']=='List' && isset($element['columns'])){
				foreach($element['columns'] as &$column){
					$fixCaption($column);					
				}
			}
		}
		if(!empty($form['actions'])){
			foreach($form['actions'] as $j=>&$element){
				if($element['type']=='Button')$fixCaption($element);
				if($element['type']=='List' && isset($element['columns'])){
					foreach($element['columns'] as &$column){
						$fixCaption($column);					
					}
				}
				if($element['type']=='TestCenter'){
					unset($form['actions'][$j]);
				}
			}
			$form['actions']=array_values($form['actions']);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see IPSModule::Create()
	 */
	public function Create(){
		parent::Create();
		$this->RegisterPropertyInteger('Mode', 0);
		$this->RegisterPropertyInteger('DataIndex', 1);
		$this->RegisterPropertyBoolean('IncludeName', true);
		$this->RegisterPropertyBoolean('CreateModule', true);
		if(IPS_GetKernelVersion()>5.0)
			$this->RegisterAttributeString('Data', '');
		else $this->RegisterPropertyString('Data', '');
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
  		$caption= IPS_VERSION<5?"label":"caption";
  		$mode=$this->ReadPropertyInteger('Mode');
  		$f=["elements"=>[["name"=>"Mode","type"=>"Select","caption"=>"Mode","options"=>[["label"=>"Backup","value"=>0],["label"=>"Restore","value"=>1]]]]];
  		$bkname='-';
  		$fehler="Error";if(IPS_VERSION>4.2)$fehler=$this->Translate($fehler);
  		if($d=$this->restore_backup()){
  			$bkmodule=$d['ModuleInfo']['ModuleName'];
  			$bkname=$d['name'];
  		} else $bkmodule=$this->Translate('empty');
  		if($mode==1){
  			$f['elements'][]=["name"=>"CreateModule","type"=>"CheckBox", "caption"=>"Create Instance if not selected"];
  			$f['elements'][]=["name"=> "IncludeName","type"=> "CheckBox",  "caption"=> "Restore instance name"];
  			$code="";
  			if(!$this->ReadPropertyBoolean('CreateModule'))
  				$code="if(!\$instance)echo 'please select instance first';else";
  			$lable='Instance "(%s) %s" created';
  			if(IPS_VERSION>4.2)$lable=$this->Translate($lable);
  			$lable=sprintf($lable,$bkmodule,$bkname);
   			$code.="if(MBKRE_RestoreModuleConfiguration($this->InstanceID,(int)\$instance,0))echo '$lable';else echo '$fehler';";
  		}else {
  			$lable="Instance \"(%s) %s\" saved. Please reload form!";
  			if(IPS_VERSION>4.2)$lable=$this->Translate($lable);
  			$lable=sprintf($lable,$bkmodule,$bkname);
  			
  			$code="if(!\$instance)echo 'please select instance first';elseif(MBKRE_BackupModuleConfiguration($this->InstanceID,(int)\$instance,0))echo '$lable';else echo '$fehler'";
  			
  		}
  		if(!($list = json_decode(IPS_GetKernelVersion()>5.0?$this->ReadAttributeString('Data'):$this->ReadPropertyString('Data'),true)))$list=[];
  		$options=[];
  		for($j=1;$j<=$this->MaxBackups;$j++){
  			if($mode==0||!empty($list[$j]))$options[]=["value"=>$j,"label"=>"$j. ".(empty($list[$j])?$this->Translate('Empty'):$list[$j]['name'])];
  		}
  		$f['elements'][]=["name"=> "DataIndex","type"=> "Select",  "caption"=> "Backup index", "options"=>$options];
  		$f['elements'][]=["type"=>"Label", "caption"=>sprintf(IPS_VERSION>4.2?$this->Translate("Current backup class : %s"):"Current backup class : %s",$bkmodule)];	
  		$f['elements'][]=["type"=>"Label", "caption"=>sprintf(IPS_VERSION>4.2?$this->Translate("Backup instance name : %s"):"Backup instance name : %s",$bkname)];	
        
  		if(IPS_VERSION>=5){
  			$f['actions'][]=["type"=>"RowLayout", "items"=>[
	   			["name"=> "instance", "type"=> "SelectInstance", "caption"=> "Module InstanceID" ],
	  			["type"=>"Button",$caption=>$mode?"Execute restore":"Execute backup", "onClick"=>$code]
  			]];
  		}else {
 	   		$f['actions'][]=["name"=> "instance", "type"=> "SelectInstance", "caption"=> "Module InstanceID" ];
	  		$f['actions'][]=["type"=>"Button",$caption=>$mode?"Execute restore":"Execute backup", "onClick"=>$code];
  		}
  		if($mode==0 && $bkname!='-'){
  			$lable="Backup of \"(%s) %s\" deleted. Please reload form!";if(IPS_VERSION>4.2)$lable=$this->Translate($lable);
  			$lable=sprintf($lable,$bkmodule,$bkname);
  			$del="Delete backup %s";if(IPS_VERSION>4.2)$del=$this->Translate($del);
    		$f['actions'][]=["type"=>"Button",$caption=>sprintf($del,$bkmodule), "onClick"=>"if(MBKRE_DeleteModuleBackup($this->InstanceID,null))echo '$lable';else echo '$fehler';"];
  			
  		}
	  	return json_encode($f);
  	}
	
	/**
	 * @param int $ModuleInstanceID InstanzeID zum sichern
	 * @param int $Index Sicherungs Index 1 bis 5 oder null für Konfigurierten Sicherungs Index
	 * @throws Exception
	 * @return boolean True wenn kein Fehler aufgetreten
	 */
	public function BackupModuleConfiguration(int $ModuleInstanceID, int $Index=null){
		if(IPS_InstanceExists($ModuleInstanceID)){
			$Data = IPS_GetInstance($ModuleInstanceID);
			$Data['name']=IPS_GetName($ModuleInstanceID);
			$Data['@config'] = json_decode(IPS_GetConfiguration($ModuleInstanceID),true);
			$this->store_backup($Data,$Index);
			return true;
		} else throw new Exception(sprintf($this->Translate("Instance %s not found"),$ModuleInstanceID),E_USER_ERROR);
	}
	/**
	 * @param int $NewModuleInstanceID InstanzeID zum zurücksichern oder 0 wenn automatische Erstellung aktiviert ist
	 * @param int $Index Sicherungs Index 1 bis 5 oder null für Konfigurierten Sicherungs Index
	 * @throws Exception
	 * @return boolean True wenn kein Fehler aufgetreten
	 */
	public function RestoreModuleConfiguration(int $NewModuleInstanceID, int $Index=null){
		if(IPS_InstanceExists($NewModuleInstanceID) || $this->ReadPropertyBoolean('CreateModule')){
			if(!$restore=$this->restore_backup($Index)){
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
	/**
	 * @param int $Index Sicherungs Index 1 bis 5 oder null für Konfigurierten Sicherungs Index
	 * @return boolean True wenn kein Fehler aufgetreten
	 */
	public function DeleteModuleBackup(int $Index=null){
		return $this->store_backup(null,$Index);
		
	}
	private function store_backup($Data, $Index=null){
		if(!($list = json_decode(($ver=IPS_GetKernelVersion())>5.0?$this->ReadAttributeString('Data'):$this->ReadPropertyString('Data'),true)))$list=[];
		if(!$Index)$Index=$this->ReadPropertyInteger('DataIndex');
		if($Index<1||$Index>$this->MaxBackups)return false;
		$list[$Index]=$Data;
		if($ver>5.0){
			$this->WriteAttributeString('Data', json_encode($list));
		}else{
			IPS_SetProperty($this->InstanceID, 'Data', json_encode($list));
			IPS_ApplyChanges($this->InstanceID);
		}
		return true;
//		$this->SetBuffer('BACKUP_DATA', json_encode($Data));
	}
	private function restore_backup($Index=null){
		if($list = json_decode(IPS_GetKernelVersion()>5.0?$this->ReadAttributeString('Data'):$this->ReadPropertyString('Data'),true)){
			if(!$Index)$Index=$this->ReadPropertyInteger('DataIndex');	
			if($Index<1||$Index>$this->MaxBackups)return null;
			return empty($list[$Index])?null:$list[$Index];
		}
	}
	
	
}
?>