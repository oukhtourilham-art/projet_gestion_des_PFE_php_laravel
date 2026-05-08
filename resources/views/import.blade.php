<!DOCTYPE html>
<html>
<head>
    <title>Import Data</title>
</head>
<body>
<h2>Import Students</h2>
<form action="{{ route('import.students') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <input type="file" name="excel_file" required>

    <select name="filiere" required>
        <option value="ID">ID</option>
        <option value="TDIA">TDIA1</option>
        <option value="GI">GI</option>
    </select>

    <button type="submit">Import Students</button>
</form>
<hr>
<h2>Import Professors</h2>

<form action="{{ route('import.professors') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="excel_file" required>
    <button type="submit">Import Professors</button>
</form>
@if(session('success'))
    <p>{{ session('success') }}</p>
@endif

</body>
</html>