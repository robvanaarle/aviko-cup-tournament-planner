<?php

namespace ats;

class Ticket {
  public $assignIndex = 0;
  public $subTickets = array();
  
  public function __construct($assignIndex) {
    $this->assignIndex = $assignIndex;
  }
  
  public function addSubTicket(SubTicket $subTicket) {
    $this->subTickets[] = $subTicket;
  }
  
  
  public function count() {
    return count($this->subTickets);
  }
  
  public function equalSubTicketTeams(Ticket $ticket) {
    if ($this->count() != $ticket->count()) {
      return false;
    }
    
    $haystack = $ticket->subTickets;
    
    foreach ($this->subTickets as $needle) {
      foreach ($haystack as $index => $compare) {
        if ($needle->equalTeams($compare)) {
          unset($haystack[$index]);
          continue 2;
        }
      }
      return false;
    }
    
    return true;
  }
  
  public function equalSubTicket(Ticket $ticket) {
    if ($this->count() != $ticket->count()) {
      return false;
    }
    
    $haystack = $ticket->subTickets;
    
    foreach ($this->subTickets as $needle) {
      foreach ($haystack as $index => $compare) {
        if ($needle->equals($compare)) {
          unset($haystack[$index]);
          continue 2;
        }
      }
      return false;
    }
    
    return true;
  }
  
  public function compareStandingTo(Ticket $ticket) {
    $result = $this->subTickets[0]->standings[0]->compareTo($ticket->subTickets[0]->standings[0]);
    
    // if the subtickets are equal (standings should always be equal), then prefer lower assign indices
    if ($this->subTickets[0]->equalTeams($ticket->subTickets[0])) {
      if ($result != 0) {
        throw new \Exception("Equal subticket teams with different standings: " . $this . " <-> " . $ticket);
      }
      return ($ticket->subTickets[0]->assignIndex - $this->subTickets[0]->assignIndex);
    }
    
    return $result;
  }

  public function getMutations() {
    $mutations = array();
    for ($assignIndex = 0; $assignIndex < $this->count(); $assignIndex++) {
      $ticket = clone $this;
      $ticket->assignIndex = $assignIndex;
      $mutations[] = $ticket;
    }
    return $mutations;
  }
  
  public function __toString() {
    //if ($this->count() == 0) {
    //  return '[]^' . ($this->assignIndex+1);
    //} elseif ($this->count() == 1) {
    //  return (string)$this->subTickets[0];
    //}
    return '(' . implode(' v ', $this->subTickets) . ')^' . ($this->assignIndex+1);
  }
}