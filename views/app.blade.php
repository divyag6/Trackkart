<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=.5, maximum-scale=1">
	<title>TrackKart</title>
    
	
    <link rel="stylesheet" href="/css/theme.blue.css">
    <link rel="stylesheet" href="/css/theme.bootstrap.css">
	<link href="/css/app.css" rel="stylesheet">
	
	
	
	<!-- CDN for bootstrap and tablesorter -->
	<!-- <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet"> -->
	<!-- <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet"> -->
	<!--<link href="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.21.4/addons/pager/jquery.tablesorter.pager.min.css" rel=stylesheet">  -->
	<!-- <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.21.4/addons/pager/jquery.tablesorter.pager.min.js"></script> -->
	
	<!-- Fonts -->
	<link href='//fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<body>
	
	
    
     <!-- Nav bar -->
     
	 @section('nav')
	 	
		@include('includes.nav')
			
	@show
    
    <!-- Page Content --> 
    
    <div class='container-fluid'>
    	@include('includes.flash')
     	
     	
     	<div id="main" class="row" >
     		<!-- sidebar content -->
     		<div id="sidebar">
				@yield('sidebar')
			</div>
			<div id="content">
			<!-- main content -->
				@yield('content')
			</div>
		</div>
   	</div>
	
	<!-- Footer -->
	
	<div class='container-fluid'>
	@section('footer')
     	
		@include('includes.footer')
		
     @show
     </div>
     
	<!-- Scripts -->
	<!-- <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		 <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js"></script>-->
	<script  type="text/javascript" src="/js/jquery/jquery.js"></script>
	<script  type="text/javascript" src="/js/bootstrap.js"></script>
	<script type="text/javascript" src="/js/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="/js/jquery.tablesorter.widgets.js"></script>
	<script src="/js/app.js"></script>
	
	<script type="text/javascript">
	$(function(){
		var today = new Date();
		var date_30 =new Date(today-30*24*60*60*1000);
		var date_60 =new Date(today-60*24*60*60*1000);
		var date_90 =new Date(today-90*24*60*60*1000);
		
		$("#table").tablesorter({
		
		    widthFixed : false,
		    widgets: ["filter"],
		   
		    widgetOptions : {
		      filter_childRows   : false,
		      filter_hideFilters : false,
		      filter_ignoreCase  : true,
			  filter_columnFilters :true,
			  
		      filter_functions : {
		       
        		1 :  {
        				"last 30 days" : function(e, n, f, i, $r, c, data) { 
            				var d= new Date(n);
            				return d>=date_30; },
            			"last 2 months" : function(e, n, f, i, $r, c, data) { 
                			var d= new Date(n);
                			return d>=date_60; },
                		"last 3 months" : function(e, n, f, i, $r, c, data) { 
                    		var d= new Date(n);
                    		return d>=date_90; }
  		       		},
        		2 : function(e, n, f, i, $r, c, data) {
		          	return e === f;
		        	}, 
        	
		        3 :	{
			      "< &#8377; 500"          : function(e, n, f, i, $r, c, data) { return n < 500; },
		          "&#8377;500 - &#8377;1000"  : function(e, n, f, i, $r, c, data) { return n >= 500 && n <1000; },
		          "&#8377;1000 - &#8377;5000" : function(e, n, f, i, $r, c, data) { return n >= 1000 && n<5000; },
		          "> &#8377;5000" 		  : function(e, n, f, i, $r, c, data) { return n >= 5000; }
		        }
		      }
		    }
		  });
		//$("#table").tablesorter(); 
	})();
	</script>
	<script type="text/javascript">
	$(function(){
		$('div.alert').not('.alert-important').delay(3000).slideUp(300);
	})();
	</script>
</body>
</html>
