Option Explicit
Dim conn, rs, path, tableName, i
path = WScript.Arguments(0)
tableName = WScript.Arguments(1)
If WScript.Arguments.Count < 2 Then
    WScript.Echo "Uso: cscript exportar_mdb_colunas.vbs arquivo.mdb Tabela"
    WScript.Quit 1
End If

Set conn = CreateObject("ADODB.Connection")
conn.Open "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=" & path & ";"
Set rs = conn.Execute("SELECT TOP 1 * FROM [" & tableName & "]")
For i = 0 To rs.Fields.Count - 1
    WScript.Echo rs.Fields(i).Name
Next
rs.Close
conn.Close
