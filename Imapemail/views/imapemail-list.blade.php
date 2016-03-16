<h1>
    My Todos
</h1>
<ul>
    @foreach ($imapemails as $imapemail)
    <li>{{$imapemail->todo}}</li>
    @endforeach
</ul>