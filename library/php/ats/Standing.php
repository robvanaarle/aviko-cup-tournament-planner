<?php

namespace ats;

interface Standing {
  public function compareTo(Standing $standing);
  public function equals(Standing $standing);
}