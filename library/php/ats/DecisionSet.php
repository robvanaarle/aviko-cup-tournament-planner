<?php

namespace ats;

class DecisionSet {
  public $decisions = array();
  public $more = false;
  
  public function count() {
    return count($this->decisions);
  }
  
  public function merge(DecisionSet $set) {
    foreach ($set->decisions as $decision) {
      $this->addDecision($decision);
    }
    $this->more |= $set->more;
  }
  
  public function addDecision(Decision $decision) {
    foreach ($this->decisions as $compare) {
      if ($decision->equals($compare)) {
        return;
      }
    }
    $this->decisions[] = $decision;
  }
  
  static public function fromTicketGroups(array $ticketGroups) {
    $set = new DecisionSet();
    
    foreach ($ticketGroups as $ticketGroup) {
        $set->merge(self::fromTicketGroup($ticketGroup));
    }
    
    return $set;
  }
  
  static public function fromTicketGroup(TicketGroup $ticketGroup) {
    $set = new DecisionSet();
    
    foreach ($ticketGroup->tickets as $ticket) {
        $set->merge(self::fromTicket($ticket));
    }
    
    return $set;
  }
  
  static public function fromTicket(Ticket $ticket) {
    $set = new DecisionSet();
    
    $ticketDecision = new Decision();
    
    foreach ($ticket->subTickets as $subTicket) {
      if ($subTicket->count() > 1) {
        $decision = new Decision();
        $decision->standings = $subTicket->standings;
        $set->addDecision($decision);
        $set->more = $ticket->count() > 1;
      } else {
        $ticketDecision->standings[] = $subTicket->standings[0];
      }
    }
    
    if ($set->count() == 0 && $ticketDecision->count() > 1) {
      $set->addDecision($ticketDecision);
    } else {
      
    }
    
    return $set;
  }
  
  public function __toString() {
    return implode("\n", $this->decisions) . " (" . ($this->more ? 'more' : 'final') . ')';
  }
}