<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:mods="http://www.loc.gov/mods/v3"
	xmlns:etd="http://www.ndltd.org/standards/metadata/etdms/1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0" exclude-result-prefixes="etd xsi">

	<xsl:output method="xml" indent="yes" omit-xml-declaration="no"/>
	<xsl:strip-space elements="*"/>

	<!-- 1: Output <title> elements -->
	<xsl:template match="mods:title | mods:titleInfo">
		<title><xsl:value-of select="."/></title>
	</xsl:template>


	<!-- 2: Output <name> elements -->
	<xsl:template match="mods:name">
		<name>
		    <xsl:for-each select="mods:namePart">
		        <xsl:sort select="position()" data-type="number" order="descending"/>
		        <xsl:value-of select="."/><xsl:text>; </xsl:text>
		    </xsl:for-each>
		</name>
	</xsl:template>

	<!--
	  -
	  - DO NOT MODIFY ANYTHING BELOW THIS LINE
	  - UNLESS YOU KNOW WHAT YOU ARE DOING!
	  -
	-->

	<!-- Canonical identity transform -->
	<!--
	<xsl:template match="@* | node()">
		<xsl:copy>
			<xsl:apply-templates select="@* | node()"/>
		</xsl:copy>
	</xsl:template>
	-->

	<!-- Namespace-aware identity transform -->
	<!-- Copy elements -->
	<xsl:template match="*" priority="-1">
	   <xsl:element name="{name()}">
	      <xsl:apply-templates select="node()|@*"/>
	   </xsl:element>
	</xsl:template>

	<!-- Copy all other nodes -->
	<xsl:template match="node()|@*" priority="-2">
	   <xsl:copy />      
	</xsl:template>

	<!-- add namespace prefix -->
	<xsl:template match="mods:mods">
		<!--
		<xsl:element name="mods:{local-name()}" namespace="http://www.loc.gov/mods/v3">
			<xsl:copy-of select="namespace::*"/>
			<xsl:apply-templates select="node()|@*"/>
		</xsl:element>
		-->
		<object>
			<xsl:apply-templates/>
		</object>
	</xsl:template>
	
	<xsl:template match="mods:*"/>

</xsl:stylesheet>