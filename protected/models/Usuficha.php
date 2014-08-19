<?php

/**
 * This is the model class for table "gi_usuficha".
 *
 * The followings are the available columns in table 'gi_usuficha':
 * @property integer $id_usuario
 * @property string $nombre
 * @property string $apellidos
 * @property string $sexo
 * @property integer $tipo_doc_iden
 * @property string $doc_iden
 * @property integer $pais_nacimiento
 * @property string $lugar_nacimiento
 * @property string $fecha_nacimiento
 * @property string $direccion
 * @property string $localidad
 * @property string $codpostal
 * @property integer $provincia
 * @property string $telefono
 * @property string $biografia
 *
 * The followings are the available model relations:
 * @property Usuario $idUsuario
 */
class Usuficha extends PHActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Usuficha the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'gi_usuficha';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id_usuario, nombre, apellidos, sexo', 'required', 'on'=>'insert,update'),
			array('nombre, apellidos, sexo', 'required', 'on'=>'porlotes'),
			array('id_usuario, tipo_doc_iden,pais_residencia, pais_nacimiento, provincia', 'numerical', 'integerOnly'=>true,'allowEmpty'=>true),
			array('nombre, apellidos, doc_iden, lugar_nacimiento', 'length', 'max'=>128),
			array('sexo', 'length', 'max'=>5),
			array('direccion, localidad, telefono', 'length', 'max'=>256),
			array('codpostal', 'length', 'max'=>10),
			array('fecha_nacimiento', 'FechaValidator'), 
			array('pais_nacimiento,pais_residencia','DatosMValidator','tipo'=>'pais'),
			array('tipo_doc_iden','DatosMValidator','tipo'=>'doc_iden'),
			array('provincia','DatosMValidator','tipo'=>'provincia'),
			array('biografia', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id_usuario, nombre, apellidos, sexo, tipo_doc_iden, doc_iden, pais_nacimiento, lugar_nacimiento, fecha_nacimiento, direccion, localidad, codpostal, provincia, telefono, biografia', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'idUsuario' => array(self::BELONGS_TO, 'Usuario', 'id_usuario'),
		);
	}
	public function onBeforeValidate($event) {
		// Verificar que no tengan opciones de edicion en el select.
		// en caso de tenerlas retirarlas. 
		// TODO: FACER Colocarlo cono una conducta para las todas las clases.
		$reglas = $this->rules();
		foreach ($reglas as $regla) {
			if ( $regla[1] == 'DatosMValidator' ) {
				$lista = explode(",",$regla[0] );
				foreach ($lista as $attr) {
					$attr = trim($attr);
					if (UtilGIeaem::hayListDataExtras($this->$attr) )
						$this->setAttribute($attr, NULL);
				}
			}
		}
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id_usuario' => 'Id Usuario',
			'nombre' => 'Nombre',
			'apellidos' => 'Apellidos',
			'sexo' => 'Sexo',
			'tipo_doc_iden' => 'Tipo Documento de  Identidad',
			'doc_iden' => 'Documento de Identidad',
			'pais_nacimiento' => 'Pais de Nacimiento',
			'lugar_nacimiento' => 'Lugar de Nacimiento',
			'fecha_nacimiento' => 'Fecha de Nacimiento',
			'direccion' => 'Dirección',
			'localidad' => 'Localidad',
			'codpostal' => 'Código postal',
			'provincia' => 'Provincia',
			'pais_residencia' => 'País de residencia',
			'telefono' => 'Teléfono',
			'biografia' => 'Biografía',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id_usuario',$this->id_usuario);
		$criteria->compare('nombre',$this->nombre,true);
		$criteria->compare('apellidos',$this->apellidos,true);
		$criteria->compare('sexo',$this->sexo,true);
		$criteria->compare('tipo_doc_iden',$this->tipo_doc_iden);
		$criteria->compare('doc_iden',$this->doc_iden,true);
		$criteria->compare('pais_nacimiento',$this->pais_nacimiento);
		$criteria->compare('lugar_nacimiento',$this->lugar_nacimiento,true);
		$criteria->compare('fecha_nacimiento',$this->fecha_nacimiento,true);
		$criteria->compare('direccion',$this->direccion,true);
		$criteria->compare('localidad',$this->localidad,true);
		$criteria->compare('codpostal',$this->codpostal,true);
		$criteria->compare('provincia',$this->provincia);
		$criteria->compare('pais_residencia',$this->pais_residencia);		
		$criteria->compare('telefono',$this->telefono,true);
		$criteria->compare('biografia',$this->biografia,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	/**
	 * Consultas directas a la base de datos sin pasar por el estudio de la estructura
	 */
	 public static function cmquery($tipo,$a1="",$a2="",$a3="") {
		switch ($tipo) {
			/* Ejemplo de uso
			case  'todos':
				return Yii::app()->db->createCommand('SELECT t.id,t.nombre FROM gi_usuficha t')->queryAll();
				break;
			*/
			default:
				return array();
		}

	 }
	 /**
	 *
	 * Utíl para mostralo en un Select para escoger uno de los tipos.
	 * @return array:string
	 */
	public static function getSexos($estados=NULL) {
	 		static $estados = array(
	 			'v'=>'Hombre',
	 			'h'=>'Mujer',
			 );
	 	return (isset($estado))? (isset($estados[$estado])?$estados[$estado]:false):$estados;
	}
	 
}