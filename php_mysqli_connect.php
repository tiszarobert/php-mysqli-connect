/*
    Created by Tisza Róbert
    Email: tiszarobi[at]gmail[dot]com
    Web: tiszarobert.hu
*/

<?php
  class Database {
    public function __construct($user, $password, $database, $host = 'localhost') {
      $this->user = $user;
      $this->password = $password;
      $this->database = $database;
      $this->host = $host;
      $this->db = new mysqli($this->host, $this->user, $this->password, $this->database);
    }
    public function query($query) {
      $result = $this->db->query($query);
      
      while ( $row = $result->fetch_object() ) {
        $results[] = $row;
      }
      
      return $results;
    }
    public function insert($table, $data) {
      if ( empty( $table ) || empty( $data ) ) {
        return false;
       }
      
      $data = (array) $data;
      
      
      foreach($data AS $element){
        $format .= (is_numeric(element))?'i':'s';
      }
      
      list( $fields, $placeholders, $values ) = $this->create_query($data);
      
      array_unshift($values, $format); 

      $prepare = $this->db->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");

      call_user_func_array(array($prepare, 'bind_param'), $this->ref_values($values));
      
      $prepare->execute();
      
      if ( $prepare->affected_rows ) {
        return true;
      }
      
      return false;
    }
    public function update($table, $data, $where) {
      
      if ( empty( $table ) || empty( $data ) ) {
        return false;
      }
      
      $data = (array) $data;
      
      foreach($data AS $element){
        $format .= (is_numeric(element))?'i':'s';
      }
      foreach($where AS $where_element){
        $where_format .= (is_numeric($where_element))?'i':'s';
      }
      $format .= $where_format;
      
      list( $fields, $placeholders, $values ) = $this->create_query($data, 'update');
      
      $where_clause = '';
      $where_values = '';
      $count = 0;
      
      foreach ( $where as $field => $value ) {
        if ( $count > 0 ) {
          $where_clause .= ' AND ';
        }
        
        $where_clause .= $field . '=?';
        $where_values[] = $value;
        
        $count++;
      }

      array_unshift($values, $format);
      $values = array_merge($values, $where_values);

      $prepare = $this->db->prepare("UPDATE {$table} SET {$placeholders} WHERE {$where_clause}");
      
      call_user_func_array(array($prepare, 'bind_param'), $this->ref_values($values));
      
      $prepare->execute();
      
      if ( $prepare->affected_rows ) {
        return true;
      }
      
      return false;
    }
    public function select($query, $data) {
     
      $prepare = $this->db->prepare($query);
      
      foreach($data AS $element){
        $format .= (is_numeric(element))?'i':'s';
      }
      
      array_unshift($data, $format);
      
      call_user_func_array(array($prepare, 'bind_param'), $this->ref_values($data));
     
      $prepare->execute();
      
      $result = $prepare->get_result();
      
      while ($row = $result->fetch_object()) {
        $results[] = $row;
      }

      return $results;
    }
    public function delete($table, $id) {

      $prepare = $this->db->prepare("DELETE FROM {$table} WHERE ID = ?");
      
      $prepare->bind_param('d', $id);
      
      $prepare->execute();
      
      if ( $prepare->affected_rows ) {
        return true;
      }
    }
    private function create_query($data, $type='insert') {
      $fields = '';
      $placeholders = '';
      $values = array();
            
      foreach ( $data as $field => $value ) {
        $fields .= "{$field},";
        $values[] = $value;
        
        if ( $type == 'update') {
          $placeholders .= $field . '=?,';
        } else {
          $placeholders .= '?,';
        }
        
      }
      $fields = substr($fields, 0, -1);
      $placeholders = substr($placeholders, 0, -1);
      
      return array( $fields, $placeholders, $values );
    }
    
    private function ref_values($array) {
      $refs = array();

      foreach ($array as $key => $value) {
        $refs[$key] = &$array[$key]; 
      }

      return $refs; 
    }
  }
?>