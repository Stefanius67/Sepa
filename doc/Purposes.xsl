<?xml version="1.0" encoding="UTF-8"?>
<html xsl:version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<head>
	<title>Transformed XML</title>
	<style>
	table { border-collapse: collapse; border-spacing: 0; border: 1px solid #777; }
	table tr th { background-color: #bbb; }
	table tr:nth-child(2n) { background-color: #eee; }
	</style>
</head>
<body>
	<table>
		<tbody>
			<tr>
				<th>Code</th>
				<th>Class</th>
				<!-- <th>Name</th> -->
				<th>Description</th>
			</tr>
			<xsl:for-each select="Purposes/Purpose">
				<tr>
					<td><xsl:value-of select="@Code"/></td>
					<td><xsl:value-of select="@Class"/></td>
					<!-- <td><xsl:value-of select="@Name"/></td> -->
					<td><xsl:value-of select="current()"/></td>
				</tr>
			</xsl:for-each>
		</tbody>
	</table>
</body>
</html> 