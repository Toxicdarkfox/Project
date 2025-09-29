const express = require('express');
const PDFDocument = require('pdfkit'); // We'll install this next
const fs = require('fs');
const path = require('path');

const app = express();

// For serving static files like your logo
app.use(express.static('public'));

// Route to generate certificate
app.get('/generate-certificate', (req, res) => {
    const doc = new PDFDocument();
    const fileName = `certificate-${Date.now()}.pdf`;

    // Set response headers
    res.setHeader('Content-Disposition', `attachment; filename="${fileName}"`);
    res.setHeader('Content-Type', 'application/pdf');

    // Pipe PDF to response
    doc.pipe(res);

    // Add coffee-theme background color
    doc.rect(0, 0, doc.page.width, doc.page.height).fill('#f5f0e6');

    // Add School Logo
    doc.image(path.join(__dirname, 'public', 'logo.png'), 50, 50, { width: 100 });

    // Add School Name
    doc.fontSize(30).fillColor('#6f4e37').text('TechAriel', { align: 'center', underline: true });

    // Add Certificate Text
    doc.moveDown(4);
    doc.fontSize(24).fillColor('#000').text('Certificate of Completion', { align: 'center' });

    doc.moveDown(2);
    doc.fontSize(18).text('This certificate is proudly presented to:', { align: 'center' });

    // Example: Student Name from query string
    const studentName = req.query.name || 'Student Name';
    doc.moveDown();
    doc.fontSize(22).fillColor('#6f4e37').text(studentName, { align: 'center', bold: true });

    // Add Date
    const today = new Date().toLocaleDateString();
    doc.moveDown(2);
    doc.fontSize(16).fillColor('#000').text(`Date: ${today}`, { align: 'center' });

    doc.end();
});

app.listen(3000, () => {
    console.log('Server running on http://localhost:3000');
});
