<?php
require('fpdf/fpdf.php');

// --- Extended FPDF class to support rounded rectangles ---
class PDF_RoundedRect extends FPDF {
    public $extgstates = array();

    function RoundedRect($x, $y, $w, $h, $r, $style='') {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F') $op='f';
        elseif($style=='FD' || $style=='DF') $op='B';
        else $op='S';
        $MyArc = 4/3 * (sqrt(2)-1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));

        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k ));
        $this->_Arc($xc+$r*$MyArc, $y+$r-($r*$MyArc), $xc+$r, $y+$r, $xc+$r, $y+$r);

        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k ));
        $this->_Arc($x+$w-$r+$r*$MyArc, $yc+$r*$MyArc, $x+$w, $yc+$r, $x+$w, $yc+$r);

        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', $xc, ($hp-($y+$h))*$k ));
        $this->_Arc($x+$r-$r*$MyArc, $y+$h-$r+$r*$MyArc, $x+$r, $y+$h-$r, $x+$r, $y+$h-$r);

        $xc = $x+$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $x, ($hp-$yc)*$k ));
        $this->_Arc($x+$r-$r*$MyArc, $y+$r-$r*$MyArc, $x+$r, $y+$r, $x+$r, $y+$r);

        $this->_out($op);
    }
    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ',
            $x1*$this->k, ($this->h-$y1)*$this->k,
            $x2*$this->k, ($this->h-$y2)*$this->k,
            $x3*$this->k, ($this->h-$y3)*$this->k));
    }

    // Optional: set transparency for watermark
    function SetAlpha($alpha) {
        $this->extgstates['alpha'] = $alpha;
        $this->_out(sprintf('/GS%d gs', count($this->extgstates)));
    }
}

// --- Create PDF ---
$pdf = new PDF_RoundedRect('L','mm','A4');
$pdf->AddPage();

// --- Colors ---
$darkCoffee = [111, 78, 55];   
$lightCoffee = [248, 240, 240]; 
$accent = [210, 180, 140];      

// Background
$pdf->SetFillColor(...$lightCoffee);
$pdf->Rect(0,0,297,210,'F');

// Rounded border
$pdf->SetDrawColor(...$darkCoffee);
$pdf->SetLineWidth(5);
$pdf->RoundedRect(10,10,277,190,10,'D');

// TechAriel logo
$pdf->Image('techariel-logo.png',20,15,30);

// --- Watermark ---
$pdf->Image('pc.png', 50, 50, 200); // adjust position/size

// --- Text ---
$pdf->SetFont('Arial','B',28);
$pdf->SetTextColor(...$darkCoffee);
$pdf->Cell(0,20,'TechAriel Academy',0,1,'C');

$pdf->SetDrawColor(...$accent);
$pdf->SetLineWidth(1.5);
$pdf->Line(60, 50, 237, 50);

$pdf->SetFont('Arial','B',42);
$pdf->SetTextColor(...$darkCoffee);
$pdf->Cell(0,25,'Certificate of Completion',0,1,'C');

$pdf->SetFont('Arial','',20);
$pdf->Cell(0,10,'This is proudly presented to',0,1,'C');

$pdf->SetFont('Arial','B',30);
$pdf->SetTextColor(...$accent);
$recipient = 'Test User';
$pdf->Cell(0,15,$recipient,0,1,'C');

$pdf->SetFont('Arial','',20);
$pdf->SetTextColor(...$darkCoffee);
$pdf->Cell(0,10,'For successfully completing the course',0,1,'C');

$pdf->SetFont('Arial','B',26);
$course = 'Introduction to Cybersecurity';
$pdf->SetTextColor(...$accent);
$pdf->Cell(0,15,$course,0,1,'C');

$pdf->SetFont('Arial','',18);
$pdf->Cell(0,10,"Date: ".date('F d, Y'),0,1,'C');

// Signature lines
$pdf->SetY(-50);
$pdf->SetFont('Arial','',16);
$pdf->Cell(100,10,'______________________',0,0,'C');
$pdf->Cell(100,10,'______________________',0,1,'C');
$pdf->Cell(100,10,'Instructor',0,0,'C');
$pdf->Cell(100,10,'Principal',0,1,'C');

// --- Save PDF ---
if(!file_exists(__DIR__.'/certificates')) {
    mkdir(__DIR__.'/certificates', 0777, true);
}
$pdf->Output('F', __DIR__.'/certificates/certificate_4_1.pdf');

echo "Certificate generated successfully!";
?>
