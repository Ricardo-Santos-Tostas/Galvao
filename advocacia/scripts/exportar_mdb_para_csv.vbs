Option Explicit
' Exporta tabela Access (.mdb Jet 4) para CSV — usar cscript 32 bits (SysWOW64).
Dim conn, rs, path, tableName, outPath, fso, out, i, val, line
If WScript.Arguments.Count < 3 Then
    WScript.Echo "Uso: cscript exportar_mdb_para_csv.vbs arquivo.mdb Tabela saida.csv"
    WScript.Quit 1
End If

path = WScript.Arguments(0)
tableName = WScript.Arguments(1)
outPath = WScript.Arguments(2)

Function CsvCampo(s)
    If IsNull(s) Then
        CsvCampo = ""
        Exit Function
    End If
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

Set conn = CreateObject("ADODB.Connection")
conn.Open "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=" & path & ";"
Set rs = CreateObject("ADODB.Recordset")
rs.CursorLocation = 3 ' adUseClient
rs.Open "SELECT * FROM [" & tableName & "] ORDER BY CADASTRO", conn, 3, 1

line = ""
For i = 0 To rs.Fields.Count - 1
    If i > 0 Then line = line & ";"
    line = line & CsvCampo(rs.Fields(i).Name)
Next
out.WriteLine line

Dim total, pulados
total = 0
pulados = 0

Do While Not rs.EOF
    On Error Resume Next
    Err.Clear
    line = ""
    For i = 0 To rs.Fields.Count - 1
        If i > 0 Then line = line & ";"
        val = rs.Fields(i).Value
        If Err.Number <> 0 Then
            val = ""
            Err.Clear
        End If
        line = line & CsvCampo(val)
    Next
    If Err.Number <> 0 Then
        pulados = pulados + 1
        Err.Clear
    Else
        out.WriteLine line
        total = total + 1
    End If
    On Error GoTo 0
    rs.MoveNext
Loop

rs.Close
conn.Close
out.Close

WScript.Echo "Exportado: " & outPath & " (" & total & " linhas, " & pulados & " ignoradas)"
