<?php
set_time_limit(0);

/**
 * Job.Importklanten API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_job_importklanten_spec(&$spec) {
  //$spec['magicword']['api.required'] = 1;
}

/**
 * Job.Importklanten API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_importklanten($params) {
  /*if (array_key_exists('magicword', $params) && $params['magicword'] == 'sesame') {
    $returnValues = array( // OK, return several data rows
      12 => array('id' => 12, 'name' => 'Twelve'),
      34 => array('id' => 34, 'name' => 'Thirty four'),
      56 => array('id' => 56, 'name' => 'Fifty six'),
    );
    // ALTERNATIVE: $returnValues = array(); // OK, success
    // ALTERNATIVE: $returnValues = array("Some value"); // OK, return a single value

    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
    return civicrm_api3_create_success($returnValues, $params, 'NewEntity', 'NewAction');
  } else {
    throw new API_Exception(/*errorMessage*/ /*'Everyone knows that the magicword is "sesame"', /*errorCode*/ /*1234);
  }*/
  
  // get all coaches
  $sql = "SELECT * FROM coaches";
  $dao = CRM_Core_DAO::executeQuery($sql);
  
  $coaches = array();
  while ($dao->fetch()) {
    if(!empty($dao->CoachId)){
      $coaches[$dao->CoachId] = array();
      $coaches[$dao->CoachId]['CoachId'] = $dao->CoachId;
      $coaches[$dao->CoachId]['CoachNaam'] = $dao->CoachNaam;
      $coaches[$dao->CoachId]['contact_id'] = $dao->contact_id;
    }
  }
   
  // get all locaties
  $sql = "SELECT * FROM locaties";
  $dao = CRM_Core_DAO::executeQuery($sql);
  
  $locaties = array();
  while ($dao->fetch()) {
    if(!empty($dao->LocatieId)){
      $locaties[$dao->LocatieId] = array();
      $locaties[$dao->LocatieId]['LocatieId'] = $dao->LocatieId;
      $locaties[$dao->LocatieId]['Locatie'] = $dao->Locatie;
      $locaties[$dao->LocatieId]['group_id'] = $dao->group_id;
    }
  }
    
  // loop trough all klanten and aan group and relationship coached by
  $sql = "SELECT * FROM klanten";
  $dao = CRM_Core_DAO::executeQuery($sql);
  
  $error = array();
  $error['first_name'] = array();
  $error['last_name'] = array();
  $error['contact'] = array();
  $error['group_id'] = array();
  $error['locaties'] = array();
  $error['coach_id'] = array();
  $error['relationship_getsingle'] = array();
  $error['start_date'] = array();
  $error['end_date'] = array();
  $error['relationship'] = array();
  
  $i = 0;
  
  while ($dao->fetch()) {    
    echo('Klantcode: ' . $dao->Klantcode) . '<br/>' . PHP_EOL;
    
    // get contact_id from civicrm
    $contact_id = 0;
    
    $result = '';
    $params = array();
    $params['sequential'] = 1;
    $params['contact_sub_type'] = 'Klant';
    
    $data = array();
    $data['first_name'] = trim($dao->Voornaam);
    $data['last_name'] = trim($dao->Klantnaam);
    $data['street_address'] = trim($dao->Adres);
    $data['intake'] = trim($dao->Intake);
    $data['minbehaald'] = trim($dao->MinBehaald);
    $data['synthese'] = trim($dao->Synthese);
        
    if(!isset($data['first_name']) or empty($data['first_name'])){
      echo('Error. Klantcode: ' . $dao->Klantcode . '. No first name !') . '<br/>' . PHP_EOL;
      $error['first_name'][$dao->Klantcode] = 'Error. Klantcode: ' . $dao->Klantcode . '. No first name !';
      continue;
    }else {
      $params['first_name'] = $data['first_name'];
    }
    
    if(!isset($data['last_name']) or empty($data['last_name'])){
      echo('Error. Klantcode: ' . $dao->Klantcode . '. No last name !') . '<br/>' . PHP_EOL;
      $error['last_name'][$dao->Klantcode] = 'Error. Klantcode: ' . $dao->Klantcode . '. No last name !';
      continue;
    }else {
      $params['last_name'] = $data['last_name'];
    }
    
    if(isset($data['street_address']) and !empty($data['street_address'])){
      $params['street_address'] = $data['street_address'];
    }
        
    try{
      $result = civicrm_api3('Contact', 'getsingle', $params);
    }catch (Exception $e) {
      echo ('Error Contact Single. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage()) . '<br/>' . PHP_EOL;
      $error['contact'][$dao->Klantcode] = 'Error Contact Single. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage();
      continue;
    } 
        
    if(isset($result['is_error']) and $result['is_error']){
      echo('Error Contact Single is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message']) . '<br/>' . PHP_EOL;
      $error['contact'][$dao->Klantcode] = 'Error Contact Single is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message'];
      continue;
    }
      
    $contact_id = $result['contact_id'];
    echo('contact_id: ' . $contact_id) . '<br/>' . PHP_EOL;
    
    // add contact to group
    $result = '';
    $params = array();
    $params['sequential'] = 1;
    $params['status'] = 'Added';
    $params['contact_id'] = $contact_id;
     
    echo('LocatieId: ' . $dao->LocatieId) . '<br/>' . PHP_EOL;
    
    $data['group_id'] = trim($locaties[$dao->LocatieId]['group_id']);
    
    echo('group_id: ' . $data['group_id']) . '<br/>' . PHP_EOL;
    
    if(!isset($data['group_id']) or empty($data['group_id'])){
      echo('Error. Klantcode: ' . $dao->Klantcode . '. No group id !') . '<br/>' . PHP_EOL;
      $error['group_id'][$dao->Klantcode] = 'Error. Klantcode: ' . $dao->Klantcode . '. No group id !';
      
    }else {
      $params['group_id'] = $data['group_id'];
      
      try{
        $result = civicrm_api3('GroupContact', 'create', $params);
      }catch (Exception $e) {
        echo ('Error GroupContact create. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage()) . '<br/>' . PHP_EOL;
        $error['locaties'][$dao->Klantcode] = 'Error GroupContact create. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage();
      }
      
      if(isset($result['is_error']) and $result['is_error']){
        echo('Error GroupContact create is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message']) . '<br/>' . PHP_EOL;
        $error['locaties'][$dao->Klantcode] = 'Error GroupContact create is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message'];
      }
    }
    
    // add relationship to contact
    $result = '';
    $params = array();
    $params['sequential'] = 1;
    $params['is_active'] = 1;
    $params['contact_id_a'] = $contact_id;
    $params['relationship_type_id'] = 13;
     
    echo('CoachId: ' . $dao->CoachId) . '<br/>' . PHP_EOL;
    
    $data['coach_id'] = trim($coaches[$dao->CoachId]['contact_id']);
    
    echo('coach_id: ' . $data['coach_id']) . '<br/>' . PHP_EOL;
    
    if(!isset($data['coach_id']) or empty($data['coach_id'])){
      echo('Error. Klantcode: ' . $dao->Klantcode . '. No coach id !') . '<br/>' . PHP_EOL;
      $error['coach_id'][$dao->Klantcode] = 'Error. Klantcode: ' . $dao->Klantcode . '. No coach id !';
    }else {
      $params['contact_id_b'] = $data['coach_id'];
            
      // get relationship
      try{
        $result = civicrm_api3('Relationship', 'getsingle', $params);
      }catch (Exception $e) {
        echo ('Error Relationship getsingle. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage()) . '<br/>' . PHP_EOL;
        $error['relationship_getsingle'][$dao->Klantcode] = 'Error Relationship create. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage();
      }
      
      if(isset($result['is_error']) and $result['is_error']){
        echo('Error Relationship getsingle is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message']) . '<br/>' . PHP_EOL;
        $error['relationship_getsingle'][$dao->Klantcode] = 'Error Relationship getsingle is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message'];
        
      }else {
        $params['id'] = $result['id'];
      }
      
      // start_date
      if(isset($data['intake']) and !empty($data['intake']) and '0000-00-00' != $data['intake']){
        $params['start_date'] = $data['intake'];
      }else {
        echo('Error. Klantcode: ' . $dao->Klantcode . '. No start date !') . '<br/>' . PHP_EOL;
        $error['start_date'][$dao->Klantcode] = 'Error. Klantcode: ' . $dao->Klantcode . '. No start date !';
      }
      
      // end_date
      if(isset($data['synthese']) and !empty($data['synthese']) and '0000-00-00' != $data['synthese']){
        $params['end_date'] = $data['synthese'];
      }else {
        echo('Error. Klantcode: ' . $dao->Klantcode . '. No end date !') . '<br/>' . PHP_EOL;
        $error['end_date'][$dao->Klantcode] = 'Error. Klantcode: ' . $dao->Klantcode . '. No end date !';
      }
      
      try{
        /*echo('Relationship create $params:<pre>');
        print_r($params);
        echo('</pre>');*/
        
        $result = civicrm_api3('Relationship', 'create', $params);
      }catch (Exception $e) {
        echo ('Error Relationship create. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage()) . '<br/>' . PHP_EOL;
        $error['relationship'][$dao->Klantcode] = 'Error Relationship create. Klantcode: ' . $dao->Klantcode . '. ' .$e->getMessage();
      }
      
      if(isset($result['is_error']) and $result['is_error']){
        echo('Error Relationship create is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message']) . '<br/>' . PHP_EOL;
        $error['relationship'][$dao->Klantcode] = 'Error Relationship create is_error. Klantcode: ' . $dao->Klantcode . '. ' . $result['error_message'];
      }
    }
    
    echo('') . '<br/>' . PHP_EOL;
    
    echo str_repeat(' ',1024*64);
    
    flush();
    ob_flush();
    sleep(1);
    
    /*if($i > 1){
      exit();
    }*/
    
    $i++;
  }
  
  echo('<pre>');
  print_r($error);
  echo('</pre>');
}

