<?php

namespace modules\scheduler\views\ats2015\helpers;

class Ticket extends \ultimo\phptpl\mvc\Helper {
  protected $ticket;
  
  public function __invoke(\ats\Ticket $ticket) {
    $this->ticket = $ticket;
    return $this;
  }
  
  public function toString() {
    if ($this->ticket->representsSingleTeam()) {
      return $this->standingToString($this->ticket->subTickets[0]->standings[0]);
    }
    
    $containsSubTicketWithMultipleStandings = false;
    
    $result = array();
    
    foreach ($this->ticket->subTickets as $subTicket) {
      $prefix = '';
      if ($subTicket->count() > 1) {
        $containsSubTicketWithMultipleStandings = true;
        $prefix = $subTicket->assignIndex+1 . 'e plek uit beslissing tussen ';
      }
      $result[] = $prefix . $this->standingsToString($subTicket->standings);
    }
    
    if (!$containsSubTicketWithMultipleStandings) {
      if (count($result) > 1) {
        $lastTeam = array_pop($result);
        $result = implode(', ', $result);
        $result .= ' en ' . $lastTeam;
        $result = array($result);
      }
    }
    
    if ($this->ticket->count() > 1) {
      if ($containsSubTicketWithMultipleStandings) {
        for($i = 0; $i < count($result); $i++) {
          $result[$i] = '<li>' . $result[$i] . "</li>";
        }
        array_unshift($result, $this->ticket->assignIndex+1 . 'e plek uit beslissing tussen de volgende beslissingen:' . "\n<ul>");
        
        $result[] = "</ul>";
      } else {
        $result[0] = $this->ticket->assignIndex+1 . 'e plek uit beslissing tussen ' . $result[0];
      }
    }
    
    return implode("\n", $result);
  }
  
  protected function standingsToString(array $standings) {
    $result = array();
    foreach ($standings as $standing) {
      $result[] = $this->standingToString($standing);
    }
    
    if (count($result) > 1) {
      $lastTeam = array_pop($result);
      $result = implode(', ', $result);
      $result .= ' en ' . $lastTeam;
    } else {
      return $result[0];
    }
    return $result;
    //return implode(' en ', $result);
  }
  
  protected function standingToString($standing) {
    return "<a href=". $this->engine->url(array('controller' => 'team', 'action' => 'read', 'id' => $standing->team->id)). ">" . $this->engine->escape($standing->team->name) . "</a>";
  }
  
  
}