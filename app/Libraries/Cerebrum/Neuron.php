<?php

namespace App\Libraries\Cerebrum;

class Neuron{
    
    
    
    public function recalculatePosition($newPosition, $oldPosition, $frequency)
    {
        if($oldPosition === null || $frequency == 0){
            return $newPosition;
        }
        $quantifier = 1 - (($frequency - 1) / $frequency );
        $value = ($newPosition - $oldPosition) * $quantifier;
        return round($oldPosition + $value, 4);
    }


}