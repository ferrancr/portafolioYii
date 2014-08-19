<?php
/**
* Utilidades especificas para el proyecto, empaquetadas en una única clase
*/ 
class UtilGIeaem {
	const INDEF='__indef';
	const NOVO='__novo';
	/**
	* Permite a las opciones de un select (HTML) incluir las opciones de indefinido y añadir 
	* @param array $listData array asociativo con la lista de datos a incorporar en el seledt (HTML)
	* @param variant $opciones ($array/null)  Que opciones queremos incluir
	*/
	public static function listData(array $listData, $opciones) {
		if (is_array($opciones) ) {
			if (isset($opciones['indefinido']) ) $var[self::INDEF] = "---Indefinido---";
			if (isset($opciones['novo']) ) $var[self::NOVO] = "---Añadir---";
			
		}
		foreach ($listData as $k => $v) {
			$var[$k]=$v;
		}
		return $var;
	}
	/**
	* Version especial para los Ajax Select de listData 
	* @param array $listData array asociativo con la lista de datos a incorporar en el seledt (HTML)
	* @param variant $opciones ($array/null)  Que opciones queremos incluir
	*/
	public static function AjaxSelectOptions(array $listData,$opciones) {
		$str = '';
		if (is_array($opciones) ) {
			if (isset($opciones['indefinido']) )$str .='<option value='.self::INDEF.">---Indefinido---</option>\n";
			if (isset($opciones['novo']) ) $str .='<option value='.self::NOVO.">---Añadir---</option>\n";
			
		}
		foreach ($listData as $k => $v) {
			$str .= '<option value='.$k.">$v</option>\n";
		}
		return $str;
	}
	/**
	* Indica si el value contine el valor de indefinido o insertar,
	* Se utiliza para analizar un select HTML que se haya montado por listData o AjaxSelectOption 
	* @param variant $value   
	*/

	public static function hayListDataExtras($value) {
		return( $value == UtilGIeaem::INDEF || $value == UtilGIeaem::NOVO );
	}
	/**
	* Devolver n fechas dividendo n periodos iguales entre un rango de desde hasta fecha
	*
	* @parant int periodidos número de peridodos
	* @param $dfecha Desde fecha
	* @parama $hfecha Hasta fecha
	*/
	public static function desglosarPorPeriodos($periodos,$dfecha,$hfecha) {
		$fechas = array();
		//TODO: FACER redondear
		if ($periodos == 0) return $fechas;
		$dtimestamp = CDateTimeParser::parse($dfecha,'yyyy-MM-dd');
		$htimestamp = CDateTimeParser::parse($hfecha,'yyyy-MM-dd');
		if ($dtimestamp > $htimestamp) return $fechas;
		if ($dtimestamp == $htimestamp || $periodos == 1) {
			$fechas[] = array(date('Y-m-d',$dtimestamp),date('Y-m-d',$htimestamp));
			return $fechas;	
		}
		$incremento = ($htimestamp - $dtimestamp) / $periodos;
		for ($i=0;$i < $periodos;$i++) {
			$ftimestamp = ($i+1 == $periodos )? $htimestamp:$dtimestamp+$incremento;
			$fechas[]=array(date('Y-m-d',$dtimestamp),date('Y-m-d',$ftimestamp));
			$dtimestamp = $ftimestamp;
		}
		return $fechas;
	}
	/**
	* Añadir al bloque que meuestra el estado de una operacion los errores encontrados
	* en el modelo afectado.
	*/
	public static function flashErrorModel($model) {
		$txtE=""; 
		foreach ((array) $model->getErrors() as $k => $v) {
			$txtE .= "$k:".implode('|',$v)."<br>";
		}
		Yii::app()->user->setFlash('error',$txtE);
	}
	/**
	 * 
	 * Guarda en log el error y transforma en mensaje para el usuario.
	 * @param Exception $exception
	 * @param array $errors ['numero']=mesaje. 1062, duplicado 1451 integridad refenrecial
	 */
	public static function filtrarErroresDB($exception,$errors=array()) {
		$omision = array(
			'1062'=>'Error: clave duplicada ya existe el dato en está gestión',
			'1451'=>'Error: no se puede realizar la accion por que existen referencias a este dato en otras gestiones.',
		);
		foreach ($errors as $k => $v) {
			$omision["$k"]=$v; // el array_merge no sirve para estos casos.
		}
		Yii::trace(print_r(compact('omision','errors'),true));
		$msg = $exception->getMessage();
		foreach ($omision as $error => $aviso) {
			if (preg_match("/ $error /",$msg) ) {
				Yii::log($aviso,'error');
				return $aviso; 
			}
		}
		return "Error: la petición no ha sido procesada, verifique si hay alguna incongruencia con los datos implicados en la acción que desea realizar. Revise los logs para más detalle";
	}
	/**
	 * Utilidad para desglosar los alumnos y profesores inscritos en un grupo de trabajo
	 * .
	 * @param array Grupo $inscritos la lista de inscritos
	 * @param array Alumno $alumnos 
	 * @param array Profesor $profesores
	 * @parama strign $mensaje en caso de de error
	 * @return array asociativo separando alumnos y profesores
	 */
	public static function inscritos($inscritos=NULL,$alumnos=NULL,$profesores=NULL,$mensaje=NULL){
		if ($mensaje == NULL ) $mensaje = "No hay asignado";
		return self::packProfesorAlumno($mensaje,$inscritos,$alumnos,$profesores);
	}
	/**
	 * Utilidad para desglosar los alumnos y profesores disponibles para formar un grupo de trabajo
	 * .
	 * @param array object $disponibles 
	 * @param array Alumno $alumnos 
	 * @param array Profesor $profesores
	 * @return array asociativo separando alumnos y profesores
	 */
	public static function disponibles($disponibles=NULL,$alumnos=NULL,$profesores=NULL){
		$mensaje = "No hay disponibles";
		return self::packProfesorAlumno($mensaje,$disponibles,$alumnos,$profesores);
	}
	/**
	 * Utilidad para desglosar los alumnos y profesores de una lista
	 * 
	 * @parama strign $mensaje en caso de de error
	 * @param array Object $todos
	 * @param array Alumno $alumnos 
	 * @param array Profesor $profesores
	 * @return array asociativo separando alumnos y profesores
	 */
	public static function packProfesorAlumno($mensaje,$todos=NULL,$alumnos=NULL,$profesores=NULL) {
		$profesorado = "";
		$alumnado = "";
		if ($todos !== NULL) {
			foreach ($todos as $v ) {
				if ($v->id_alumno !== null ) {
					$alumnado .= $v->usuario->nombre.',';
				}
				if ($v->id_profesor != null ) {
					$profesorado .= $v->usuario->nombre.',';
				}
			}
		} else {
				foreach ($alumnos as $v ) {
					$alumnado .= $v->usuario->nombre.',';
				}
				foreach ($profesores as $v) {
					$profesorado .= $v->usuario->nombre.',';
				}
		}
		if (! strlen($profesorado) ) $profesorado = $mensaje;
		if (! strlen($alumnado) ) $alumnado = $mensaje;
		return compact('profesorado','alumnado');
		
	}

	/** 
	 * funcion cadena_fecha fechaForm2Mysql(cadena_data $f, cadena $patron)
	 * $f: la fecha en formato dd/mm/yyyy
	 * $patron: La cadena separadora entre dia mes y anio. Por omision "/"
	 * Devuelve: la fecha en formato MySQL "yyyy-mm-dd" o nada si la fecha es incorrecta.
	*/
	public static function fechaForm2Mysql ($f, $patron="/") {
		//echo "split($patron,$f);";
		$algo = @split($patron,$f);
		// miramos si la fecha es correcta
		if (!(@checkdate($algo[1],$algo[0],$algo[2]))) {
			return "";
		} else {
			// vale la fecha y la convertimos a formato MySQL
			$f = sprintf('%d-%02d-%02d',$algo[2],$algo[1],$algo[0]);
			return $f;
		}
	}
	/**
	 * funcion: cadena fechaMysql2Form(cadena $formato, cadena_con_data_MysQL $datasql)
	 * @param boolean largo: boolean si que queire formar con horas minutos segundos o no. 
	 * @param string formato: Opcional Es un string con un formato valido de maquetación PHP.
	 * @param string datasql: Es un string con el formato MySQL yyy-mm-dd o yyyy-mm-dd hh:mm:ss
	 * @return string de fecha, devuelve: una cadena con data convertida según la variable $formato o las  definida por omision.
	*/
	public static function fechaMysql2Form ($datasql,$largo=false,$formato='') {
	  if ($datasql == "0000-00-00" || $datasql == "0000-00-00 00:00:00" || empty($datasql) ) {
		  return "";
		}
		if ($largo) {
			$formato = empty($formato)?"d/m/Y H:i:s":$formato;
		} else {
			$formato = empty($formato)?"d/m/Y":$formato;
		}
 		if (strstr($formato,':')) {
			preg_match("/^(\d+)-(\d+)-(\d+) (\d+)\:(\d+)\:(\d+)/",$datasql,$v );
			return date( $formato, mktime((int)$v[4],(int)$v[5],(int)$v[6],(int)$v[2],(int)$v[3],(int)$v[1]) );
		} else {
			preg_match("/^(\d+)?.(\d+)?.(\d+)/",$datasql,$v );
			return date( $formato, mktime(0,0,0,(int)$v[2],(int)$v[3],(int)$v[1]) );
		} 
	}
	
	public static function horaModeloAForm($hora) {
		return substr($hora, 0,5);
	}
	public static function horaFormAModelo($hora) {
		return "$hora:00";
	}
	public static function maquetarHorario($v,$url=true) {
		$hora = "";
		if ($url) $horario = CHtml::link(CHtml::encode(UtilGIeaem::fechaMysql2Form($v['fecha'])), array('horario/view', 'id'=>$v['id']));
		else $horario = CHtml::encode(UtilGIeaem::fechaMysql2Form($v['fecha']));
		if ('00:00:00' != $v['hora_inicio'] ) {  $hora = CHtml::encode(substr($v['hora_inicio'],0,5)); }
		if ('00:00:00' != $v['hora_inicio'] && '00:00:00' != $v['hora_final'] ) {  $hora .=' '. CHtml::encode(substr($v['hora_final'],0,5)); }
		if ($v['todo_el_dia'] ) { $hora .=' '. CHtml::encode('Todo el día'); }
		elseif ('00:00:00' != $v['hora_inicio'] && '00:00:00' == $v['hora_final'] ) {  $hora .=' '. CHtml::encode('- ¿?'); }
		if (! strlen($hora) ) $hora = "hora indefinida";
		$horario .= " ( $hora ) ";
		return $horario;
	}
	public static function horaInicial($fecha,$hora_inicio) {
		return strtotime( "$fecha $hora_inicio" );
	}
	
	public static function horaFinal($fecha,$hora_final,$todo_el_dia) {
		$timefinal = $fecha." ";
		$timefinal .= ('00:00:00' == $hora_final ||'00:00' == $hora_final || '' == $hora_final || $todo_el_dia )?'23:59:59':$hora_final;
		return strtotime( $timefinal );
	}
	public static function tiempo($horaInicial,$horaFinal) {
		if (is_null($horaInicial)||is_null($horaFinal)) return 0;
		if (! $horaFinal > 0 || ! $horaInicial > 0 ) return 0;
		//yii::trace('horaIncial='.$horaInicial.'*horaFinal='.$horaFinal.'*','tiempo');
		$d = getdate($horaFinal - $horaInicial);
		$t = "";
		if ($d['hours']>1) $t = ($d['hours']-1).'h';
		if ($d['minutes']>0) $t .= $d['minutes']."'";
		if (empty($t)) $t=0;
		return $t;
		
	}
	public static function mostrarhora($horas) {
		$h =  intval($horas);
		$m = intval(($horas- $h)*100);
		if ($m > 0) $m = intval($m * 60 / 100)."'";
		else $m='';
		return "${h}h ${m}";
	}
	/**
	 * 
	 * Devuelve el número de dias.
	 * @param mixed $fechainicial string/int
	 * @param mixed $fechafinal string/int/null
	 * @return integer Número de dias.
	 */
	public static function diffdias($fechainicial,$fechafinal=NULL) {
		if (empty($fechafinal) ) $fechafinal =time();
		if (is_string($fechainicial) ) $fechainicial = strtotime($fechainicial);
		if (is_string($fechafinal) ) $fechafinal = strtotime($fechafinal);
		return round(abs($fechafinal - $fechainicial)/60/60/24);
	}
	/**
	 * 
	 * Devuelve un array con las horas y minutos redondeados a un intervalo desde un limite inferior
	 * hasta un limite superior de la fecha Unix indicada en $hora.
	 * @param integer $hora fecha Unix
	 * @param integer $limiteInferior minutos a mirar antes.
	 * @param integer $limiteSuperior minutos a mirar despues
	 * @param integer $intervalo intervalo de minutos a saltar.
	 */
	public static function listaHoraria($hora,$limiteInferior,$limiteSuperior,$intervalo,$desde=0) {
		$inicio =  $hora - ((date('i',$hora) % $intervalo ) * 60 ) - ($limiteInferior*60);
		$final = $hora + ($limiteSuperior*60);
		$actual = $inicio;
		$lista = array();
		$incremento =  $intervalo * 60;
		while ($actual < $final) {
			if ($desde+$incremento <= $actual ) {
				$vhora = date('H:i',$actual);
				$lista[$vhora.':00'] = $vhora;
			}
			$actual += $incremento;
		}
		return $lista;
	}
	public static function getIdporNombre($model,$attribute){
		return CHtml::getIdByName(CHtml::activeName($model, $attribute));
	}
	public static function separarFiltro($value) {
		$op = "";
		if(preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/',$value,$matches)) {
        	$value=$matches[2];
        	$op=$matches[1];
    	}
    	return array($op,$value); 
	}
	/**
	 * Send mail method
	 */
	public static function sendMail($email,$subject,$message,$firma='firma_para_usuario') {
    	$adminEmail = Yii::app()->params['remitenteCorreo'];
    	$message .= Yii::t('correo',$firma,array('{remitente}'=>$adminEmail) );
    	Yii::import('application.extensions.phpmailer.JPhpMailer');
    	$mail = new JPhpMailer;
    	$mail->CharSet = 'utf-8';
		$mail->IsMail();
		$mail->IsHTML();
		$mail->SetFrom($adminEmail);
		$mail->Subject = $subject;
		$mail->MsgHTML($message);
		$mail->AddAddress($email);
		//$mail->AddAddress('ferran@digitaltotsol.com');
	    Yii::log("Correo a $email con $subject",'info','application.components.utilgieaem.sendmail');
	    $sw = $mail->send();
	    if (strlen($mail->error)) {
	    	Yii::log($mail->error,'error','application.components.utilgieaem.sendmail');
	    }
	    return $sw;
	}
	public static function yiiparam($name, $default = null)	{
	    if ( isset(Yii::app()->params[$name]) )
	        return Yii::app()->params[$name];
	    else
	        return $default;
	}
	public static function stripAccentsUTF8($string){
	return strtr(utf8_decode($string),utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'),
'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}
}
