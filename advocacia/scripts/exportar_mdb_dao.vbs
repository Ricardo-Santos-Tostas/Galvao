Option Explicit
Dim db, rs, path, tableName, outPath, fso, out, i, val, line, total, pulados
If WScript.Arguments.Count < 3 Then
    WScript.Echo "Uso: cscript exportar_mdb_dao.vbs arquivo.mdb Tabela saida.csv"
    WScript.Quit 1
End If
path = WScript.Arguments(0)
tableName = WScript.Arguments(1)
outPath = WScript.Arguments(2)

Function CsvCampo(s)
    If IsNull(s) Then CsvCampo = "" : Exit Function
    s = CStr(s)
    s = Replace(s, """", """""")
    If InStr(s, ";") > 0 Or InStr(s, """") > 0 Or InStr(s, vbCr) > 0 Or InStr(s, vbLf) > 0 Then
        CsvCampo = """" & s & """"
    Else
        CsvCampo = s
    End If
End Function

Set fso = CreateObject("Scripting.FileSystemObject")
Set out = fso.CreateTextFile(outPath, True, False)

Dim engine
On Error Resume Next
Set engine = CreateObject("DAO.DBEngine.36")
If Err.Number <> 0 Then
    Err.Clear
    Set engine = CreateObject("DAO.DBEngine.120")
End If
On Error GoTo 0
If engine Is Nothing Then
    WScript.Echo "ERRO: DAO nao disponivel"
    WScript.Quit 2
End If

Set db = engine.OpenDatabase(path, False, False)
Set rs = db.OpenRecordset("SELECT * FROM [" & tableName & "] ORDER BY CADASTRO", 4)

line = ""
For i = 0 To rs.Fields.Count - 1
    If i > 0 Then line = line & ";"
    line = line & CsvCampo(rs.Fields(i).Name)
Next
out.WriteLine line

total = 0
pulados = 0
Do While Not rs.EOF
    On Error Resume Next
    Err.Clear
    line = ""
    For i = 0 To rs.Fields.Count - 1
        If i > 0 Then line = line & ";"
        line = line & CsvCampo(rs.Fields(i).Value)
    Next
    If Err.Number <> 0 Then
        pulados = pulados + 1
        Err.Clear
    Else
        out.WriteLine line
        total = total + 1
    End If
    rs.MoveNext
    On Error GoTo 0
Loop

rs.Close
db.Close
out.Close
WScript.Echo "Exportado: " & outPath & " (" & total & " linhas, " & pulados & " ignoradas)"
