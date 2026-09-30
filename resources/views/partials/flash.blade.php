@if (session('success'))
    <div class="alert alert-success">✅ {{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-error">⛔ {{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        <b>Проверьте заполнение полей:</b>
        <ul style="margin:6px 0 0;padding-left:18px">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif