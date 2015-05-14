<?php

var_dump( preg_match("/^-?[0-9]+$/", "12"));

exit();
interface Standing {
  public function compareTo(Standing $standing);
  public function equals(Standing $standing);
}

class ArrayStanding implements Standing {
  public $name;
  public $points;
  
  public function __construct($name, $points) {
    $this->name = $name;
    $this->points = $points;
  }
  
  public function compareTo(Standing $standing) {
    return $this->points - $standing->points;
  }
  
  public function equals(Standing $standing) {
    return $this->name == $standing->name;
  }
  
  static public function fromGroups(array $groups) {
    $result = array();
    foreach ($groups as $group) {
      $resultGroup = array();
      foreach ($group['standings'] as $standing) {
        $resultGroup[] = new ArrayStanding($standing['name'], $standing['points']);
      }
      $result[] = $resultGroup;
    }
    return $result;
  }
  
  public function __toString() {
    return $this->name;
  }
}

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
  
  public function isFull() {
    return count($this->tickets) >= $this->size;
  }
  
  public function normalize() {
    $newGroup = new TicketGroup($this->size);
    
    // normale tickets with equal sub-tickets regarding teams
    $ticketBuckets = array();
    foreach ($this->tickets as $ticket) {
      for ($i=0; $i<count($ticketBuckets); $i++) {
        if ($ticket->equalSubTicket($ticketBuckets[$i][0])) {
          $ticketBuckets[$i][] = $ticket;
          continue 2;
        }
        
      }
      $ticketBuckets[] = array($ticket);
    }
    

    $normalizedTickets = array();
    foreach ($ticketBuckets as $ticketBucket) {
      //echo "-" . implode(', ', $ticketBucket) . "\n";
      
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
            $normalizedTicket->equalSubTicketTeams($subTicketBuckets[$i][0])) {
          $subTicketBuckets[$i][] = $normalizedTicket;
          continue 2;
        }
        
      }
      $subTicketBuckets[] = array($normalizedTicket);
    }
    
    foreach ($subTicketBuckets as $subTicketBucket) {
      //echo "*" . implode(', ', $subTicketBucket) . "\n";
      
      if ($subTicketBucket[0]->count() == 1) {
        $subTicketCount = $subTicketBucket[0]->subTickets[0]->count();
      } else {
         $subTicketCount = 0;
         foreach ($subTicketBucket[0]->subTickets as $subTicket) {
           if ($subTicket->count() == 1) {
             $subTicketCount++;
           }
         }
      }
      
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
  
  public function __toString() {
    if (count($this->tickets) == 0) {
      return '[]';
    }
    return implode(', ', $this->tickets);
  }
}

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

class Decision {
  public $standings = array();
  
  public function count() {
    return count($this->standings);
  }
  
  public function equals(Decision $decision) {
    if ($this->count() != $decision->count()) {
      return false;
    }
    
    $haystack = $decision->standings;
    
    foreach ($this->standings as $needle) {
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
  
  public function __toString() {
    return implode(" v ", $this->standings);
  }
  
}

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
        throw new Exception("Equal subticket teams with different standings: " . $this . " <-> " . $ticket);
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

class SubTicket {
  public $assignIndex = 0;
  public $standings = array();
  public $group;
  
  public function __construct($assignIndex, $group) {
    $this->assignIndex = $assignIndex;
    $this->group = $group;
  }
  
  public function addStanding(Standing $standings) {
    $this->standings[] = $standings;
  }
  
  public function count() {
    return count($this->standings);
  }
  
  public function equals(SubTicket $subTicket) {
    return $this->assignIndex == $subTicket->assignIndex && $this->equalTeams($subTicket);
  }
  
  public function equalTeams(SubTicket $subTicket) {
    if ($this->count() != $subTicket->count()) {
      return false;
    }
    
    $haystack = $subTicket->standings;
    
    foreach ($this->standings as $needle) {
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
  
  public function getMutations() {
    $mutations = array();
    for ($assignIndex = 0; $assignIndex < $this->count(); $assignIndex++) {
      $subTicket = clone $this;
      $subTicket->assignIndex = $assignIndex;
      $mutations[] = $subTicket;
    }
    return $mutations;
  }
  
  public function __toString() {
    //if ($this->count() == 0) {
    //  return '[]^' . ($this->assignIndex+1);
    //} elseif ($this->count() == 1) {
     //   return $this->teams[0];
    //}
    
    $teams = $this->standings;
    if (is_array($teams[0])) {
      for ($i=0; $i<$this->count(); $i++) {
        $teams[$i] = $teams[$i]['name'];
      }
    }
    
    return '(' . implode(' v ', $teams) . ')^' . ($this->assignIndex+1);
  }
}

/*
$group = new TicketGroup(2);

$ticket1 = new Ticket(0);$group->addTicket($ticket1);
$subTicket = new SubTicket(0, 0);$ticket1->addSubTicket($subTicket);
$subTicket->addStanding(new ArrayStanding('A1', 100));

$ticket1 = new Ticket(0);$group->addTicket($ticket1);
$subTicket1 = new SubTicket(0, 1);$ticket1->addSubTicket($subTicket1);
$subTicket1->addStanding(new ArrayStanding('A3', 90));$subTicket1->addStanding(new ArrayStanding('A2', 90));

$ticket1 = new Ticket(0);$group->addTicket($ticket1);
$subTicket1 = new SubTicket(0, 2);$ticket1->addSubTicket($subTicket1);
$subTicket1->addStanding(new ArrayStanding('A4', 80));$subTicket->addStanding(new ArrayStanding('A5', 80));

$ticket2 = new Ticket(0);$group->addTicket($ticket2);
$subTicket2 = new SubTicket(1, 1);$ticket2->addSubTicket($subTicket2);
$subTicket2->addStanding(new ArrayStanding('A2', 90));$subTicket2->addStanding(new ArrayStanding('A3', 90));

echo $group;
echo "\n";
$group = $group->normalize();
echo $group;
exit();*/


function nextPhaseNew2(array $standingGroups) {

  $ticketGroups = array();
  $indices = 0;
  foreach ($standingGroups as $index => $standings) {
    $ticketGroup = TicketGroup::fromStandings($standings, $index);
    $ticketGroups[] = $ticketGroup;
    $indices = max($indices, $ticketGroup->size);
  }
   
  
  $indexTickets = array();
  $indexTicketGroups = array();
  for ($i = 0; $i < $indices; $i++) {
    $indexGroup = array();
    
    foreach ($ticketGroups as $j => $ticketGroup) {
      if ($i < count($ticketGroup->tickets)) {
        $indexGroup[] = $ticketGroup->tickets[$i];
      }
    }
    
    usort($indexGroup, function($a, $b) {
      return $b->compareStandingTo($a);
    });
    
    $indexTicketGroup = TicketGroup::fromIndexTickets($indexGroup);
    $indexTicketGroups[] = $indexTicketGroup;
    $indexTickets = array_merge($indexTickets, $indexTicketGroup->tickets);
  }
  
  $targetGroups = array();
  $i = 0;
  foreach ($standingGroups as $index => $standings) {
    $targetGroup = new TicketGroup(count($standings));
    foreach ($standings as $standing) {
      $targetGroup->addTicket($indexTickets[$i]);
      $i++;
    }
    
    $targetGroups[] = $targetGroup->normalize();
  }
  
  return $targetGroups;
}

$poules = array(
  array(
    'name' => 'A',
    'standings' =>
      array(
        array('name' => 'A1', 'points' => 99),
        array('name' => 'A2', 'points' => 91),
        array('name' => 'A3', 'points' => 60),
        array('name' => 'A4', 'points' => 60),
        array('name' => 'A5', 'points' => 60)
      )
  ),
  array(
    'name' => 'B',
    'standings' =>
      array(
        array('name' => 'B1', 'points' => 99),
        array('name' => 'B2', 'points' => 90),
        array('name' => 'B3', 'points' => 85),
        array('name' => 'B4', 'points' => 85),
        array('name' => 'B5', 'points' => 61)
      ),
  ),
  array(
    'name' => 'C',
    'standings' =>
      array(
        array('name' => 'C1', 'points' => 99),
        array('name' => 'C2', 'points' => 90),
        array('name' => 'C3', 'points' => 85),
        array('name' => 'C4', 'points' => 85),
        array('name' => 'C5', 'points' => 62)
      ),
  ),
  array(
    'name' => 'D',
    'standings' =>
      array(
        array('name' => 'D1', 'points' => 99),
        array('name' => 'D2', 'points' => 90),
        array('name' => 'D3', 'points' => 85),
        array('name' => 'D4', 'points' => 85),
        array('name' => 'D5', 'points' => 63)
      )
   ),
);

$poules = array(
  array(
    'name' => 'A',
    'standings' =>
      array(
        array('name' => 'A', 'points' => 100),
        array('name' => 'B', 'points' => 98),
        array('name' => 'C', 'points' => 98),
        array('name' => 'D', 'points' => 96),
        array('name' => 'E', 'points' => 60),
        //array('name' => 'X', 'points' => 50)
      )
  ),
  array(
    'name' => 'B',
    'standings' =>
      array(
        array('name' => 'F', 'points' => 99),
        array('name' => 'G', 'points' => 98),
        array('name' => 'H', 'points' => 98),
        array('name' => 'I', 'points' => 97),
        array('name' => 'J', 'points' => 50),
        //array('name' => 'Y', 'points' => 50)
      ),
  )
);

/*$poules = array(
  array(
    'name' => 'A',
    'standings' =>
      array(
        array('name' => 'A', 'points' => 100),
        array('name' => 'B', 'points' => 65)
      )
  ),
  array(
    'name' => 'B',
    'standings' =>
      array(
        array('name' => 'F', 'points' => 90),
        array('name' => 'G', 'points' => 90)
      ),
  ),
  array(
    'name' => 'C',
    'standings' =>
      array(
        array('name' => 'K', 'points' => 90),
        array('name' => 'L', 'points' => 90)
      ),
  )
);*/


//$group = TicketGroup::fromStandings($poules[0]['standings']);
//echo $group;
$groups = ArrayStanding::fromGroups($poules);
$result = nextPhaseNew2($groups);
echo "\n";

foreach ($result as $index => $group) {
  echo 'P' . $index . ': ' . $group . "\n";
}
echo "\n";
echo DecisionSet::fromTicketGroups($result);

exit();
