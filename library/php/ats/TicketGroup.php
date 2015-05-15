<?php

namespace ats;

class TicketGroup {
  public $size;
  public $tickets = array();
  
  public function __construct($size) {
    $this->size = $size;
  }
  
  static public function fromStandings(array $standings, $sourceGroup) {
    $group = new TicketGroup(count($standings));
    $subTicket = new SubTicket(0, $sourceGroup);
    
    $standingsCount = count($standings);
    for ($i = 0; $i<$standingsCount; $i++) {
      $standing = $standings[$i];
      $subTicket->addStanding($standing);
      
      $nextStanding = null;
      if ($i+1 < $standingsCount) {
        $nextStanding = $standings[$i+1];
      }
      
      if ($nextStanding === null || $nextStanding->compareTo($standing) != 0) {
        foreach ($subTicket->getMutations() as $mutation) {
          $ticket = new Ticket(0);
          $ticket->addSubTicket($mutation);
          $group->addTicket($ticket);
        }
        $subTicket = new SubTicket(0, $sourceGroup);
      }
    }
    
    return $group;
  }
  
  static public function fromIndexTickets(array $tickets) {
    $group = new TicketGroup(count($tickets));
    $targetTicket = new Ticket(0);
    
    $ticketsCount = count($tickets);
    for ($i = 0; $i < count($tickets); $i++) {
      $ticket = $tickets[$i];
      $targetTicket->addSubTicket($ticket->subTickets[0]);
      
      $nextTicket = null;
      if ($i+1 < $ticketsCount) {
        $nextTicket = $tickets[$i+1];
      }
      
      if ($nextTicket === null || $nextTicket->compareStandingTo($ticket) != 0) {
        foreach ($targetTicket->getMutations() as $mutation) {
          $group->addTicket($mutation);
        }
        $targetTicket = new Ticket(0);
      }
    }
    
    return $group;
  }
  
  public function count() {
    return count($this->tickets);
  }
  
  public function equals(TicketGroup $ticketGroup) {
    if ($this->count() != $ticketGroup->count()) {
      return false;
    }
    
    $haystack = $ticketGroup->tickets;
    
    foreach ($this->tickets as $needle) {
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
  
  public function isFull() {
    return count($this->tickets) >= $this->size;
  }
  
  public function normalize() {
    $newGroup = new TicketGroup($this->size);
    
    // normale tickets with equal sub-tickets regarding teams
    $ticketBuckets = array();
    foreach ($this->tickets as $ticket) {
      for ($i=0; $i<count($ticketBuckets); $i++) {
        if ($ticket->hasEqualSubTickets($ticketBuckets[$i][0])) {
          $ticketBuckets[$i][] = $ticket;
          continue 2;
        }
        
      }
      $ticketBuckets[] = array($ticket);
    }
    

    $normalizedTickets = array();
    foreach ($ticketBuckets as $ticketBucket) {
      //echo "-" . implode(', ', $ticketBucket) . "<br />";
      
      if (count($ticketBucket) == $ticketBucket[0]->count()) {
        foreach ($ticketBucket[0]->subTickets as $subTicket) {
          $newTicket = new Ticket(0);
          $newTicket->addSubTicket($subTicket);
          $normalizedTickets[] = $newTicket;
        }
      } else {
        foreach ($ticketBucket as $ticket) {
          $normalizedTickets[] = $ticket;
        }
      }
    }
    
    //$newGroup->tickets = $normalizedTickets;
    
    // TODO: normalize single subTickets
    $subTicketBuckets = array();
    foreach ($normalizedTickets as $normalizedTicket) {
      for ($i=0; $i<count($subTicketBuckets); $i++) {
        //if ($ticket->equalSubTicket($subTicketBuckets[$i][0])) {
        if ($normalizedTicket->count() == 1 &&
            $normalizedTicket->assignIndex == 0 &&
            $normalizedTicket->subTickets[0]->assignIndex != $subTicketBuckets[$i][0]->subTickets[0]->assignIndex &&
            $normalizedTicket->hasEqualSubTicketTeams($subTicketBuckets[$i][0])) {
          $subTicketBuckets[$i][] = $normalizedTicket;
          continue 2;
        }
        
      }
      $subTicketBuckets[] = array($normalizedTicket);
    }
    
    foreach ($subTicketBuckets as $subTicketBucket) {
      //echo "*" . implode(', ', $subTicketBucket) . "<br />";
      
      
      
      if ($subTicketBucket[0]->count() == 1) {
        $subTicketCount = $subTicketBucket[0]->subTickets[0]->count();
      } else {
         $subTicketCount = 0;
         foreach ($subTicketBucket[0]->subTickets as $subTicket) {
           if ($subTicket->count() == 1) {
             $subTicketCount++;
           } else {
             $subTicketCount = -1;
             break;
           }
         }
      }
      
      //echo count($subTicketBucket) . ' <-> ' . $subTicketBucket[0]->subTickets[0]->count() . '/' . $subTicketCount .  '<br />';
      
      if (count($subTicketBucket) == $subTicketCount) {
      //if (count($subTicketBucket) == $subTicketBucket[0]->subTickets[0]->count()) {
        foreach ($subTicketBucket[0]->subTickets[0]->standings as $standing) {
          $newTicket = new Ticket(0);
          $newSubTicket = new SubTicket(0, $subTicketBucket[0]->subTickets[0]->group);
          $newSubTicket->addStanding($standing);
          $newTicket->addSubTicket($newSubTicket);
          $newGroup->addTicket($newTicket);
        }
      } else {
        foreach ($subTicketBucket as $ticket) {
          $newGroup->addTicket($ticket);
        }
      }
    }
    
    
    return $newGroup;
  }
  
  public function addTicket(Ticket $ticket) {
    $this->tickets[] = $ticket;
  }
  
  public function addTeam($team) {
    $ticket = new Ticket(0);
    $ticket->addTeam($team);
    $this->addTicket($ticket);
  }
  
  public function __toString() {
    if (count($this->tickets) == 0) {
      return '[]';
    }
    return implode(', ', $this->tickets);
  }
}