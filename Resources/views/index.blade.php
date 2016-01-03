<!DOCTYPE html>
<html>
<head>
<title>Articles</title>
<style>
body {font-family: Helvetica, serif;}
h1{color: #888;}
</style>
</head>
<body>

<h1>Articles</h1>

@if ($articles)
<ul>
	@foreach ($articles['articles'] as $article)
	<li>
		<a>{{ $article->title }}</a>
	</li>
	@endforeach
</ul>
@endif

</body>
</html>
