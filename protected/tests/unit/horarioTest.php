<?php
Yii::import('application.models.Horario');
class horarioTest extends CDbTestCase {
	public $fixtures = array (
		'gi_actividad'=>':gi_actividad',
		'gi_horario'=>':gi_horario',
		'gi_actividad_grupo'=>':gi_actividad_grupo',
		'gi_actgrupo_inscrito'=>':gi_actgrupo_inscrito',

	);
	public static  function setUpBeforeClass() {
		$name = Yii::app()->getDb()->createCommand('SELECT database() as "nombre" ')->queryRow();
		echo "BASEDEDATOS={$name['nombre']}\n";
		if ($name['nombre']!= 'gi_eaem2ut') die("no estas en la base de datos de test");
	}
	protected function setUp() {
		parent::setUp();
	}
	public function testSelect() {
		/* CADUCADO
		$model = Horario::model()->findByPk(2);
		//print_r($model->attributes);
		$this->assertEquals($model->id, 2);
		$this->assertEquals($model->id_actividad, 2 );
		$this->assertEquals($model->id_grupo, 2);
		echo Horario::model()->tableName();
		$elemento="grupo";$fecha="2011-09-30";$hora_inicio="10:00:00";$hora_fin="13:00:00";$inElementos=NULL;$volcar=True;
		//print_r(Horario::createquery('ocupados',$fecha,$hora_inicio,$hora_fin));
		$q= Horario::cmQueryOcupadosFecha($elemento, $fecha, $hora_inicio, $hora_fin,$inElementos);
		//print_r($q);
		$r = array(array(1,1),array(1,2),array(1,3),array(2,2),array(2,3));
		$this->assertEquals(count($q),count($r));
		$total=count($r);
		for($i=0;$i < $total; $i++) {
			$this->assertEquals($q[$i]['id_actividad'],$r[$i][0]);
			$this->assertEquals($q[$i]['id_usuario'],$r[$i][1]);
		}
		$inElementos=array(3,4); // Usuarios a observar.
		$q= Horario::cmQueryOcupadosFecha($elemento, $fecha, $hora_inicio, $hora_fin,$inElementos);
		//print_r($q);
		$r = array(array(1,3),array(2,3));
		$this->assertEquals(count($q),count($r));
		$total=count($r);
		for($i=0;$i < $total; $i++) {
			$this->assertEquals($q[$i]['id_actividad'],$r[$i][0]);
			$this->assertEquals($q[$i]['id_usuario'],$r[$i][1]);
		}
		*/
		// Demostracion que el from, select, where son unicos y machaca uno al otro.
		// con array es peor.
		 $q= Yii::app()->db->createCommand();
		 $q->from('gi_horario as h');
		 $q->from('gi_actgrupo_inscrito as g');
		 $q->select('h.id, h.fecha');
		 $q->select('g.id_usuario');
		 $q->where('g.id_usuario > 0');
		 $q->where('h.id_grupo = g.id_grupo');
		 $this->assertEquals($q->where,'h.id_grupo = g.id_grupo' );
		 //print_r($q->text);
		
		
	}
}