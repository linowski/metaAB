<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title id='html-title'>Meta-Analysis Calculator For A/B Tests | GoodUI</title>
  </head>
<body>


<div id="content">
    <div id="content-body">
        <div class="uk-text-center uk-margin-large-bottom uk-margin-medium-top">
            <div class="uk-container uk-container-large">
                <h1 class="uk-heading-medium">
                    Meta-Analysis Calculator For A/B Tests
                </h1>

                <p class="uk-text-large">
                    <div class="uk-text-large uk-margin-remove">For meta-analyzing and combining two or more a/b tests together.</div>
                    <div class="uk-text-medium uk-margin-remove">Good for apples, red, green, orange-like, pink or simply honey crisps. Maybe even oranges or fruit as well? Your call.<br></div>
                </p>

                <hr class="uk-margin-medium-top">


                <?php 


                //sanitize our inputs
                if ($_SERVER["REQUEST_METHOD"] === "POST") {
                    // Iterate through all POST input fields
                    $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_NUMBER_INT);
                }

                //turn it into an array
                $ourData = array();




                foreach ($_POST as $fieldName => $fieldValue) {
                    // Use regular expression to extract the integer
                    if (preg_match('/(\d+)/', $fieldName, $matches)) {
                        $i = $matches[0];
                    }


                    //create an array for each a/b test that will later store necessary values and calculations
                    $dynField = "A" . $i . "s";
                    if ($fieldName == $dynField && !empty($fieldValue)) {
                        $ourData[$i][$dynField] = $fieldValue;
                        $ourData[$i]["zscore"] = "";  
                        $ourData[$i]["zscore_stouffer"] = "";  
                        $ourData[$i]["p"] = null;
                        $ourData[$i]["p_stouffer"] = null;
                        $ourData[$i]["effect"] = null;  
                        $ourData[$i]["stderror"] = null;  
                    }
                    $dynField = "A" . $i . "v";
                    if ($fieldName == $dynField && !empty($fieldValue)) {
                        $ourData[$i][$dynField] = $fieldValue; 
                    }
                    $dynField = "B" . $i . "s";
                    if ($fieldName == $dynField && !empty($fieldValue)) {
                        $ourData[$i][$dynField] = $fieldValue; 
                    }
                    $dynField = "B" . $i . "v";
                    if ($fieldName == $dynField && !empty($fieldValue)) {
                        $ourData[$i][$dynField] = $fieldValue; 
                    }

                }

                // Approach
                /*
                Quick summary of approach:
                1. We take an inverse variance-weighted (precision-weighted) average of the lifts from each experiment to estimate the "true" lift that's assumed common to all experiments. This is also the maximum-likelihood estimate.
                2. Because we're working with RELATIVE lifts, we estimate the variance of the lift using the standard approach derived using the delta method.
                */


                //Calculate Z Scores And P Values
                foreach ($ourData as $loopKey => $loopValue) {
                    
                    //$loopKey holds the number of our array like 1,2,3 etc.
                    $as = "A" . $loopKey . "s";
                    $av = "A" . $loopKey . "v";
                    $bs = "B" . $loopKey . "s";
                    $bv = "B" . $loopKey . "v";
                    
                    //zscore
                    $zscore = calculateZscore($loopValue[$as],$loopValue[$bs],$loopValue[$av],$loopValue[$bv],$loopValue,$loopKey);
                    $zscore_stouffer = calculateZscore_stouffer($loopValue[$as],$loopValue[$bs],$loopValue[$av],$loopValue[$bv],$loopValue,$loopKey);

                    //pval
                    $p = calculateP($zscore);
                    $p_stouffer = calculateP_stouffer($zscore_stouffer,1);

                    //store in array
                    $ourData[$loopKey]["zscore"] = $zscore;
                    $ourData[$loopKey]["p"] = $p;
                    $ourData[$loopKey]["effect"] = calculateImprovement($loopValue[$av], $loopValue[$as], $loopValue[$bv], $loopValue[$bs], false);
                    $ourData[$loopKey]["sample"] = $loopValue[$av] + $loopValue[$bv];
                    $ourData[$loopKey]["zscore_stouffer"] = $zscore_stouffer;
                    $ourData[$loopKey]["p_stouffer"] = $p_stouffer;
                    //$ourData[$loopKey]["stderror"] = calcStdErr($loopValue[$as],$loopValue[$bs],$loopValue[$av],$loopValue[$bv],$loopValue,$loopKey);
                    $ourData[$loopKey]["stderror"] = computeStandardErrorOfRelativeLift($loopValue[$as],$loopValue[$bs],$loopValue[$av],$loopValue[$bv]);
                    

                    //negate the zscore if effect is less than 0
                    if ($ourData[$loopKey]["effect"] <= 0) {
                        $ourData[$loopKey]["zscore"] = -1 * abs($ourData[$loopKey]["zscore"]);
                        $ourData[$loopKey]["zscore_stouffer"] = -1 * abs($ourData[$loopKey]["zscore_stouffer"]);
                    }

                }



                ?>




                <div class="uk-form-controls">
                    <form method="POST" action="" >
                        
                        <div class="calcrow">
                            A/B Test 1: <input type="text" placeholder="A Successes" name="A1s" value="<?= $name = $_POST['A1s'] ?>" class="uk-input"> / <input type="text" placeholder="A Visitors" name="A1v" value="<?= $name = $_POST['A1v'] ?>" class="uk-input">
                            vs <input type="text" placeholder="B Successes" name="B1s" value="<?= $name = $_POST['B1s'] ?>" class="uk-input"> / <input type="text" placeholder="B Visitors" name="B1v" value="<?= $name = $_POST['B1v'] ?>" class="uk-input">
                            <br class="mobile-break"><strong>Effect</strong> <input type="text" placeholder="Effect" name="E1" value="<?= $ourData['1']['effect'] ?>" class="uk-input"> <strong>P</strong><input type="text" placeholder="P" name="P1" value="<?= $ourData['1']['p'] ?>" class="uk-input"> <strong>Z</strong><input type="text" placeholder="Z" name="Z1" value="<?= $ourData['1']['zscore'] ?>" class="uk-input"><br>
                            <br>
                        </div>


                        <div class="calcrow">
                            A/B Test 2: <input type="text" placeholder="A Successes" name="A2s" value="<?= $name = $_POST['A2s'] ?>" class="uk-input"> / <input type="text" placeholder="A Visitors" name="A2v" value="<?= $name = $_POST['A2v'] ?>" class="uk-input">
                            vs <input type="text" placeholder="B Successes" name="B2s" value="<?= $name = $_POST['B2s'] ?>" class="uk-input"> / <input type="text" placeholder="B Visitors" name="B2v" value="<?= $name = $_POST['B2v'] ?>" class="uk-input">
                            <br class="mobile-break"><strong>Effect</strong> <input type="text" placeholder="Effect" name="E2" value="<?= $ourData['2']['effect'] ?>" class="uk-input"> <strong>P</strong><input type="text" placeholder="P" name="P2" value="<?= $ourData['2']['p'] ?>" class="uk-input"> <strong>Z</strong><input type="text" placeholder="Z" name="Z2" value="<?= $ourData['2']['zscore'] ?>" class="uk-input"><br>
                        </div>

                        <div class="calcrow">
                            A/B Test 3: <input type="text" placeholder="A Successes" name="A3s" value="<?= $name = $_POST['A3s'] ?>" class="uk-input"> / <input type="text" placeholder="A Visitors" name="A3v" value="<?= $name = $_POST['A3v'] ?>" class="uk-input">
                            vs <input type="text" placeholder="B Successes" name="B3s" value="<?= $name = $_POST['B3s'] ?>" class="uk-input"> / <input type="text" placeholder="B Visitors" name="B3v" value="<?= $name = $_POST['B3v'] ?>" class="uk-input">
                            <br class="mobile-break"><strong>Effect</strong> <input type="text" placeholder="Effect" name="E3" value="<?= $ourData['3']['effect'] ?>" class="uk-input"> <strong>P</strong><input type="text" placeholder="P" name="P3" value="<?= $ourData['3']['p'] ?>" class="uk-input"> <strong>Z</strong><input type="text" placeholder="Z" name="Z3" value="<?= $ourData['3']['zscore'] ?>" class="uk-input"><br>
                        </div>

                        <div class="calcrow">
                            A/B Test 4: <input type="text" placeholder="A Successes" name="A4s" value="<?= $name = $_POST['A4s'] ?>" class="uk-input"> / <input type="text" placeholder="A Visitors" name="A4v" value="<?= $name = $_POST['A4v'] ?>" class="uk-input">
                            vs <input type="text" placeholder="B Successes" name="B4s" value="<?= $name = $_POST['B4s'] ?>" class="uk-input"> / <input type="text" placeholder="B Visitors" name="B4v" value="<?= $name = $_POST['B4v'] ?>" class="uk-input">
                            <br class="mobile-break"><strong>Effect</strong> <input type="text" placeholder="Effect" name="E4" value="<?= $ourData['4']['effect'] ?>" class="uk-input"> <strong>P</strong><input type="text" placeholder="P" name="P4" value="<?= $ourData['4']['p'] ?>" class="uk-input"> <strong>Z</strong><input type="text" placeholder="Z" name="Z4" value="<?= $ourData['4']['zscore'] ?>" class="uk-input"><br>
                        </div>

                        <div class="calcrow">
                            A/B Test 5: <input type="text" placeholder="A Successes" name="A5s" value="<?= $name = $_POST['A5s'] ?>" class="uk-input"> / <input type="text" placeholder="A Visitors" name="A5v" value="<?= $name = $_POST['A5v'] ?>" class="uk-input">
                            vs <input type="text" placeholder="B Successes" name="B5s" value="<?= $name = $_POST['B5s'] ?>" class="uk-input"> / <input type="text" placeholder="B Visitors" name="B5v" value="<?= $name = $_POST['B5v'] ?>" class="uk-input">
                            <br class="mobile-break"><strong>Effect</strong> <input type="text" placeholder="Effect" name="E5" value="<?= $ourData['5']['effect'] ?>" class="uk-input"> <strong>P</strong><input type="text" placeholder="P" name="P5" value="<?= $ourData['5']['p'] ?>" class="uk-input"> <strong>Z</strong><input type="text" placeholder="Z" name="Z5" value="<?= $ourData['5']['zscore'] ?>" class="uk-input"><br>
                        </div>



                        <input type="submit" name="Calculate" value="Calculate" class="uk-button uk-button-primary uk-margin-small-bottom uk-width-1-1 uk-width-auto@s uk-padding-remove-horizontal-mobile">

                    </form>


                </div>

                <hr class="uk-margin-medium-top">

                <?php 
                


                
                
                
                //META-ANALYZE
                $numoftests = 0;
                $totalZscore = 0;
                $totalZscore_stouffer = 0;
                $highestSample = 0;
                $sumOfWeights = 0;
                $metaEffect = 0;

                //count number of tests and total (sum) zscores for weighing
                foreach ($ourData as $loopKey => $loopValue) {
                    if ($ourData[$loopKey]["zscore"]) {
                        //increment number of tests
                        $numoftests += 1;

                        //update total zscore
                        $totalZscore_stouffer += $ourData[$loopKey]["zscore_stouffer"];  


                        //find highest sample 
                        if ($ourData[$loopKey]["sample"] > $highestSample) {
                            $highestSample = $ourData[$loopKey]["sample"];
                        }
                    }

                }

                //calculate weights for each a/b test
                foreach ($ourData as $loopKey => $loopValue) {
                    if ($ourData[$loopKey]["zscore"]) {
                        
                        //calculate weight and store in array (Ronny's approach)
                        //$ourData[$loopKey]["weight"] = sqrt($ourData[$loopKey]["sample"]/$highestSample);
                        
                        //calculate weight (Tyler's approach): weight = 1 / standard error **2;
                        $ourData[$loopKey]["weight"] = 1 / ($ourData[$loopKey]["stderror"] **2);

                        $metaEffect += ($ourData[$loopKey]['effect'] * $ourData[$loopKey]['weight']);

                        //sumweights
                        $sumOfWeights += $ourData[$loopKey]['weight'];
                    }
                }

                //calculate variance of metaEffect and standard Error
                if ($sumOfWeights > 0) {
                    $varMetaEffect = 1 / $sumOfWeights;
                    $se = sqrt($varMetaEffect);
                    $metaEffect = $metaEffect / $sumOfWeights;
                } 


                //Calculate Meta P-Value
                if ($numoftests > 0) {
                    //Ronny recommended Stouffer method:
                    $stouffer = $totalZscore_stouffer / sqrt($numoftests);
                    $metap_stouffer = calculateP_stouffer($totalZscore_stouffer,$numoftests);

                    //Tyler's precision-weighting (aka inverse variance-weighting)
                    $metaZscore = ($metaEffect / $se) / 100;
                    $metap = calculateP($metaZscore);

                    
                    //Output
                    echo "<strong>META RESULTS FOR RELATIVE EFFECT / Inverse Variance-weighted Method</strong><br>";
                    echo "<div style='color: #2547BE; margin: 5px 0; font-size: 22px;'>META RELATIVE EFFECT: <span style='font-weight: bold;'>$metaEffect%</span> </div>";
                    echo "<div style='color: #2547BE; margin: 5px 0; font-size: 22px;'>META PVALUE: <span style='font-weight: bold;'>$metap</span></div>";
                    echo "Number of Tests: $numoftests <br>";
                    echo "Total Zscore: $metaZscore <br>";
                    echo "Sum Of Weights: $sumOfWeights <br>";
                    echo "<hr class='uk-margin-medium-top'>";

                    //Output
                    echo "<br>";
                    echo "<strong>META RESULTS FOR ABSOLUTE EFFECT / Stouffer Method</strong><br>";
                    echo "<div style='color: #2547BE; margin: 5px 0; font-size: 22px;'>META PVALUE: <span style='font-weight: bold;'>$metap_stouffer</span></div>";
                    echo "Stouffer: $stouffer <br>";

                    $i=1;
                    foreach ($ourData as $loopKey => $loopValue) {
                        echo "Test $i P-Value: " . $ourData[$loopKey]["p_stouffer"] . "<br>";
                        $i++;
                    }
                    echo "<hr class='uk-margin-medium-top'>";
                    
                }


                //Output Full Array
                showArray($ourData);
                ?>


            </div>

        </div>
    </div>
</div>
  

</body>
</html>








<!--PHP-->

<?php



function showArray($ourData) {
    foreach ($ourData as $loopArray) {
        foreach ($loopArray as $key => $value) {
            //echo "Key: $key, Value: $value<br>";
            echo "$key = $value<br>";
        }
    
        echo "<hr>";
    }
}


function calculateZscore($controlCount, $variantCount, $controlSample, $variantSample,$arrayRef,$loopKey)
{
    if ($controlCount !== null && $variantCount!== null && $controlSample!== null && $variantSample!== null) {
        //Tyler's approach
        $controlMean = $controlCount / $controlSample;
        $variantMean = $variantCount / $variantSample;
    
        $varControlMean = $controlMean * (1 - $controlMean) / $controlSample;
        $varVariantMean = $variantMean * (1 - $variantMean) / $variantSample;

        $relativeLift = ($variantMean - $controlMean) / $controlMean;
    
        $varRelativeLift = $varVariantMean / pow($controlMean, 2) + pow($variantMean, 2) / pow($controlMean, 4) * $varControlMean;

        $z_score = $relativeLift / sqrt($varRelativeLift);

        return $z_score;
    }
    return false;
}

function calculateZscore_stouffer($controlCount, $variantCount, $controlSample, $variantSample,$arrayRef,$loopKey)
{
    if ($controlCount !== null && $variantCount!== null && $controlSample!== null && $variantSample!== null) {

        // Calculate conversion rates
        $p_A = $controlCount / $controlSample;
        $p_B = $variantCount / $variantSample;

        // Calculate the pooled standard error
        $p_pooled = ($controlCount + $variantCount) / ($controlSample + $variantSample);
        $standard_error = sqrt($p_pooled * (1 - $p_pooled) * (1 / $controlSample + 1 / $variantSample));

        // Calculate the z-score
        $z_score = ($p_B - $p_A) / $standard_error;

        return $z_score;
    }
    return false;
}


function calcStdErr($controlCount, $variantCount, $controlSample, $variantSample,$arrayRef,$loopKey)
{
    if ($controlCount !== null && $variantCount!== null && $controlSample!== null && $variantSample!== null) {
        // Calculate the pooled standard error
        $p_pooled = ($controlCount + $variantCount) / ($controlSample + $variantSample);
        $standard_error = sqrt($p_pooled * (1 - $p_pooled) * (1 / $controlSample + 1 / $variantSample));

        return $standard_error;
    }
    return false;
}

function computeStandardErrorOfRelativeLift($controlCount, $variantCount, $controlSample, $variantSample) {
    if ($controlCount !== null && $variantCount!== null && $controlSample!== null && $variantSample!== null) {
    // This function calculates the standard error of a relative lift estimate
    // computed from two proportion estimates using the delta method.
    //
    // Note that this methodology relies on two assumptions:
    // 1. Cov(control_mean, variant_mean) = 0. I.e., the estimates of the control rate and the variant rate are statistically independent.
    // 2. The first-order Taylor expansion of the ratio is a sufficient approximation. This approximation improves with larger sample sizes.

    $controlMean = $controlCount / $controlSample;
    $variantMean = $variantCount / $variantSample;

    $varControlMean = $controlMean * (1 - $controlMean) / $controlSample;
    $varVariantMean = $variantMean * (1 - $variantMean) / $variantSample;

    $varRelativeLift = $varVariantMean / pow($controlMean, 2) + pow($variantMean, 2) / pow($controlMean, 4) * $varControlMean;
    /*
    echo "varControlMean = $varControlMean<br>";
    echo "varVariantMean = $varVariantMean<br>";
    echo "varRelativeLift = $varRelativeLift<br>";
    echo "SQRT varRelativeLift = " . sqrt($varRelativeLift) . "<br><br>";
    */

    return sqrt($varRelativeLift);
    }
    return false;
}


//Absolute effects p-value 
function calculateP_stouffer($score,$numoftests)
{
    $stouffer = $score / sqrt($numoftests);
    $stouffer = abs($stouffer);

    // Calculate the two-tailed p-value using the standard normal distribution
    $p_twotail = 2 * (1 - cumnormdist($stouffer));
    
    //make sure it's not 0 as that breaks things
    if ($p_twotail < 0.000000000000001) {
        $p_twotail = 0.000000000000001;
    }
    
    $p_twotail = number_format($p_twotail, 15);

	return $p_twotail;
}


function calculateP($score)
{
    $absZ = abs($score);

    // Calculate the two-tailed p-value using the standard normal distribution
    $p_twotail = 2 * (1 - cumnormdist($absZ));
    
    //make sure it's not 0 as that breaks things
    if ($p_twotail < 0.000000000000001) {
        $p_twotail = 0.000000000000001;
    }
    
    $p_twotail = number_format($p_twotail, 15);

	return $p_twotail;
}




function cumnormdist($x)
{
    $b1 =  0.319381530;
    $b2 = -0.356563782;
    $b3 =  1.781477937;
    $b4 = -1.821255978;
    $b5 =  1.330274429;
    $p  =  0.2316419;
    $c  =  0.39894228;

    if($x >= 0.0) {
        $t = 1.0 / ( 1.0 + $p * $x );
        return (1.0 - $c * exp( -$x * $x / 2.0 ) * $t *
            ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ));
    }
    else {
        $t = 1.0 / ( 1.0 - $p * $x );
        return ( $c * exp( -$x * $x / 2.0 ) * $t *
            ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ));
    }
}




function calculateImprovement($a_visitors, $a_conversions, $b_visitors, $b_conversions, $isInverted) {
    if($a_visitors && $b_visitors && $a_conversions && $b_conversions) {
        $a = $a_conversions/$a_visitors;
        $b = $b_conversions/$b_visitors;

        if ($isInverted) {
            return number_format(((($a-$b)/$b)*100), 1);
        } else {
            return number_format(((($b-$a)/$a)*100), 1);
        }
    }
}



?>

















<!--CSS-->

<style>
    input {
        padding: 10px;
        margin: 10px !important;
        width: 100%;
        flex: 1;
    }

    input[type=submit] {
        margin: 20px 0px !important;
    }



    .calcrow {
        display: flex; /* Create a flex container */
        justify-content: space-between; /* Distribute space evenly between elements */
        align-items: center;
    }


    @media (min-width: 601px) {
        .mobile-break {
            display: none;
            flex: 1;
            margin: 10px;
            break-inside: avoid;
        }
    }

</style>