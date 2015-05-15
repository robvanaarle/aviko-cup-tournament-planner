<?php

namespace ats;

class TicketGroupTest extends \PHPUnit_Framework_TestCase {
  
  public function testTicketGroupIsFull() {
    $ticketGroup = new TicketGroup(2);
    $ticketGroup->addTicket(new Ticket(0));
    $ticketGroup->addTicket(new Ticket(0));
    
    $this->assertTrue($ticketGroup->isFull());
  }
  
  /**
   * @dataProvider normalizationProvider
   */
  public function testNormalization(TicketGroup $ticketGroup, TicketGroup $expectedTicketGroup) {
    $normalized = $ticketGroup->normalize();
    $this->assertTrue($normalized->equals($expectedTicketGroup), "normalize[{$ticketGroup}]; expected=[({$expectedTicketGroup})]; actual[({$normalized})]");
  }
 
  
  public function normalizationProvider() {
    $cases = array();
    $expected = array();
    
    // ((T1 v T2)^1)^1, ((T2 v T1)^2)^1
    //  => ((T1)^1)^1, ((T2)^1)^1
    $cases[] = array(
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1", "T2")
          )
        )
      ), 
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 1,
              'teams' => array("T2", "T1")
          )
        )
      ), 
    );
    
    $expected[] = array(
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1")
          )
        )
      ), 
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T2")
          )
        )
      ), 
    );
    
    // ((T1 v T2)^1)^1, ((T1 v T2)^2)^1
    //    => ((T1)^1), ((T2)^1)^1
    // case is unreachable?
    
    // ((T1 v T2)^1 v (T3 v T4)^1)^1, ((T1 v T2)^1 v (T3 v T4)^1)^2
    //    => ((T1 v T2)^1)^1, ((T3 v T4)^1)^1
    $cases[] = array(
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1", "T2")
          ),
          array(
              'assignIndex' => 0,
              'teams' => array("T3", "T4")
          )
        )
      ), 
      array(
        'assignIndex' => 1,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1", "T2")
          ),
          array(
              'assignIndex' => 0,
              'teams' => array("T3", "T4")
          )
        )
      ), 
    );
    
    $expected[] = array(
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1", "T2")
          )
        )
      ), 
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T3", "T4")
          )
        )
      ), 
    );
    
    
    // ((T1 v T2)^1 v (T3 v T4)^1)^1, ((T1 v T2)^2 v (T3 v T4)^2)^1, ((T1 v T2)^1 v (T3 v T4^1))^2, ((T1 v T2)^2 v (T3 v T4)^2)^2 
    //     => ((T1))^1, (T2))^1, (T3))^1, (T4))^1, 
    $cases[] = array(
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1", "T2")
          ),
          array(
              'assignIndex' => 0,
              'teams' => array("T3", "T4")
          )
        )
      ), 
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 1,
              'teams' => array("T1", "T2")
          ),
          array(
              'assignIndex' => 1,
              'teams' => array("T3", "T4")
          )
        )
      ),
      array(
        'assignIndex' => 1,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1", "T2")
          ),
          array(
              'assignIndex' => 0,
              'teams' => array("T3", "T4")
          )
        )
      ),
      array(
        'assignIndex' => 1,
        'subTickets' => array(
          array(
              'assignIndex' => 1,
              'teams' => array("T1", "T2")
          ),
          array(
              'assignIndex' => 1,
              'teams' => array("T3", "T4")
          )
        )
      ),
    );
    
    $expected[] = array(
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T1")
          )
        )
      ), 
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T2")
          )
        )
      ),
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T3")
          )
        )
      ), 
      array(
        'assignIndex' => 0,
        'subTickets' => array(
          array(
              'assignIndex' => 0,
              'teams' => array("T4")
          )
        )
      ), 
    );
    
    $result = array();
    foreach ($cases as $i => $case) {
      $result[] = array($this->ticketGroupFormArray($cases[$i]), $this->ticketGroupFormArray($expected[$i]));
    }
    
    return $result;
  }
  
  protected function ticketGroupFormArray($array) {
    $ticketGroup = new TicketGroup(count($array));
    foreach ($array as $ticketArray) {
      $ticket = new Ticket($ticketArray['assignIndex']);
      foreach ($ticketArray['subTickets'] as $subTicketArray) {
        $subTicket = new SubTicket($subTicketArray['assignIndex'], 0);
        foreach ($subTicketArray['teams'] as $team) {
          $subTicket->addStanding(new SimpleStanding($team, 100));
        }
        $ticket->addSubTicket($subTicket);
      }
      $ticketGroup->addTicket($ticket);
    }
    return $ticketGroup;
  }
}