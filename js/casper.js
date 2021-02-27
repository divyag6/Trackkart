var casper = require('casper').create();

casper.userAgent('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0');
phantom.cookiesEnabled = true;

var AMAZON_USER = 'you@yoursite.com';
var AMAZON_PASS = 'some crazy password';

casper.start('https://www.amazon.com/').thenClick('a#nav-link-yourAccount', function() {
    this.echo('Title: ' + this.getTitle());

    var emailInput = 'input#ap_email';
    var passInput  = 'input#ap_password';

    this.mouseEvent('click', emailInput, '15%', '48%');
    this.sendKeys('input#ap_email', AMAZON_USER);

    this.wait(3000, function() {
        this.mouseEvent('click', passInput, '12%', '67%');
        this.sendKeys('input#ap_password', AMAZON_PASS);

        this.mouseEvent('click', 'input#signInSubmit', '50%', '50%');
    });
});

casper.then(function(e) {
    this.wait(5000, function() {
        this.echo('Capping');
        this.capture('amazon.png');
    });
});


casper.run(function() {
    console.log('Done');

    casper.done();
});