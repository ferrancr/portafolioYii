<?php
class EventsBehavior extends CActiveRecordBehavior {
	public function beforeSave($event) {
		// tambien vale $this-owner->loquesea.
		$propietario = $this->getOwner();	
		if ($propietario->isNewRecord) {
			$propietario->fecha_creacion = date('Y-m-d H:i:s');
			if ($propietario->hasAttribute('id_creador') && empty($propietario->id_creador)) {
				$ins = ACsession::get();
				$propietario->id_creador = $ins['id_usuario'];
			}
		}
	}
	public function beforeValidate($event) {
		// Verificar que no tengan opciones de edicion en el select.
		// en caso de tenerlas retirarlas.
		$propietario = $this->getOwner(); 
		$reglas = $propietario->rules();
		foreach ($reglas as $regla) {
			if ( $regla[1] == 'DatosMValidator' ) {
				$lista = explode(",",$regla[0] );
				foreach ($lista as $attr) {
					$attr = trim($attr);
					if (UtilGIeaem::hayListDataExtras($propietario->$attr) )
						$propietario->setAttribute($attr, NULL);
				}
			}
		}
	}
	public function activo($alias="") {
		$criteria = $this->getOwner()->getDbCriteria();
		$estado =(empty($alias))?'estado':$alias.'.estado';
		$criteria->compare($estado,'activo');
		return $this->getOwner();	
	} 
	
	public function esCreador($id=NULL) {
		$este = $this->getOwner();
		if ($id == NULL) {
			$ins = ACsession::get();
			$id = $ins['id_usuario'];
		}
		return ( $este->hasAttribute('id_creador') && $este->id_creador == $id ); 
	}
}