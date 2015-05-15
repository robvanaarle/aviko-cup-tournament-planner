<?php

namespace ats;

class SubTicketTest extends \PHPUnit_Framework_TestCase {
  
  public function testSubTicketsHaveEqualTeamsInSameOrder() {
    $subTicketA = new SubTicket(0, 0);
    $subTicketB = new SubTicket(1, 1);
    
    $subTicketA->addStanding(new SimpleStanding("T1", 90));
    $subTicketA->addStanding(new SimpleStanding("T2", 80));
    
    $subTicketB->addStanding(new SimpleStanding("T2", 90));
    $subTicketB->addStanding(new SimpleStanding("T1", 80));
   
    $this->assertTrue($subTicketA->hasEqualTeams($subTicketB));
  }
  
  public function testSubTicketsHaveEqualTeamsInDifferentOrder() {
    $subTicketA = new SubTicket(0, 0);
    $subTicketB = new SubTicket(1, 1);
    
    $subTicketA->addStanding(new SimpleStanding("T1", 90));
    $subTicketA->addStanding(new SimpleStanding("T2", 80));
    
    $subTicketB->addStanding(new SimpleStanding("T2", 90));
    $subTicketB->addStanding(new SimpleStanding("T1", 80));
   
    $this->assertTrue($subTicketA->hasEqualTeams($subTicketB));
  }
  
  public function testSubTicketsWithDoubleTeamDoNotHaveEqualTeams() {
    $subTicketA = new SubTicket(0, 0);
    $subTicketB = new SubTicket(1, 1);
    
    $subTicketA->addStanding(new SimpleStanding("T1", 90));
    $subTicketA->addStanding(new SimpleStanding("T2", 80));
    $subTicketA->addStanding(new SimpleStanding("T2", 70));
    
    $subTicketB->addStanding(new SimpleStanding("T2", 90));
    $subTicketB->addStanding(new SimpleStanding("T1", 80));
   
    $this->assertFalse($subTicketA->hasEqualTeams($subTicketB));
  }
  
  public function testSubTicketsWithSameAssignIndicesAreEqual() {
    $subTicketA = new SubTicket(0, 0);
    $subTicketB = new SubTicket(0, 1);
    
    $subTicketA->addStanding(new SimpleStanding("T1", 90));
    $subTicketA->addStanding(new SimpleStanding("T2", 80));
    
    $subTicketB->addStanding(new SimpleStanding("T2", 90));
    $subTicketB->addStanding(new SimpleStanding("T1", 80));
   
    $this->assertTrue($subTicketA->equals($subTicketB));
  }
  
  public function testSubTicketsWithDifferentAssignIndicesAreNotEqual() {
    $subTicketA = new SubTicket(0, 0);
    $subTicketB = new SubTicket(1, 1);
    
    $subTicketA->addStanding(new SimpleStanding("T1", 90));
    $subTicketA->addStanding(new SimpleStanding("T2", 80));
    
    $subTicketB->addStanding(new SimpleStanding("T2", 90));
    $subTicketB->addStanding(new SimpleStanding("T1", 80));
   
    $this->assertFalse($subTicketA->equals($subTicketB));
  }
  
}