<?php

namespace ats;

class TicketTest extends \PHPUnit_Framework_TestCase {
  
  protected $subTickets;
  
  public function setUp() {
    $this->subTickets = array();
    
    $this->subTickets[] = $subTicket = new SubTicket(0, 0);
    $subTicket->addStanding(new SimpleStanding("T1", 90));
    $subTicket->addStanding(new SimpleStanding("T2", 80));
    
    $this->subTickets[] = $subTicket = new SubTicket(1, 1);
    $subTicket->addStanding(new SimpleStanding("T2", 90));
    $subTicket->addStanding(new SimpleStanding("T1", 80));
    
    $this->subTickets[] = $subTicket = new SubTicket(0, 1);
    $subTicket->addStanding(new SimpleStanding("T2", 90));
    $subTicket->addStanding(new SimpleStanding("T1", 80));
    
    $this->subTickets[] = $subTicket = new SubTicket(0, 1);
    $subTicket->addStanding(new SimpleStanding("T2", 90));
    $subTicket->addStanding(new SimpleStanding("T3", 80));
    
  }
  
  public function testTicketsHaveEqualSubTicketsInSameOrder() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    $ticketA->addSubTicket($this->subTickets[1]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[0]);
    $ticketB->addSubTicket($this->subTickets[1]);
    
    $this->assertTrue($ticketA->hasEqualSubTickets($ticketB));
  }
  
  public function testTicketsHaveEqualSubTicketsInDifferentOrder() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    $ticketA->addSubTicket($this->subTickets[1]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[1]);
    $ticketB->addSubTicket($this->subTickets[0]);
    
    $this->assertTrue($ticketA->hasEqualSubTickets($ticketB));
  }
  
  public function testTicketsDoNotHaveEqualSubTickets() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[3]);
    
    $this->assertFalse($ticketA->hasEqualSubTickets($ticketB));
  }
  
  public function testTicketsWithSameSubTicketsWithDifferentAssignIndicesDoNotHaveEqualSubTickets() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[1]);
    
    $this->assertFalse($ticketA->hasEqualSubTickets($ticketB));
  }
  
  public function testTicketsWithDuplicateSubTicketDoNotHaveEqualSubTickets() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    $ticketA->addSubTicket($this->subTickets[1]);
    $ticketA->addSubTicket($this->subTickets[1]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[1]);
    $ticketB->addSubTicket($this->subTickets[0]);
    
    $this->assertFalse($ticketA->hasEqualSubTickets($ticketB));
  }
  
  public function testTicketsWithSameSubTicketsWithDifferentAssignIndicesHaveEqualSubTicketTeams() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[1]);
    
    $this->assertTrue($ticketA->hasEqualSubTicketTeams($ticketB));
  }
  
  public function testTicketsDoNotHaveEqualSubTicketTeams() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[3]);
    
    $this->assertFalse($ticketA->hasEqualSubTicketTeams($ticketB));
  }
  
  public function testTicketsAreEqual() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    $ticketA->addSubTicket($this->subTickets[1]);
    
    $ticketB = new Ticket(0);
    $ticketB->addSubTicket($this->subTickets[0]);
    $ticketB->addSubTicket($this->subTickets[1]);
    
    $this->assertTrue($ticketA->equals($ticketB));
  }
  
  public function testTicketsAreNotEqual() {
    $ticketA = new Ticket(0);
    $ticketA->addSubTicket($this->subTickets[0]);
    $ticketA->addSubTicket($this->subTickets[1]);
    
    $ticketB = new Ticket(1);
    $ticketB->addSubTicket($this->subTickets[1]);
    $ticketB->addSubTicket($this->subTickets[0]);
    
    
    $this->assertFalse($ticketA->equals($ticketB));
  }
  
}