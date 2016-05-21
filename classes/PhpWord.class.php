<?php
require_once 'classes/PHPWord/Element/AbstractElement.php';
require_once 'classes/PHPWord/Element/AbstractContainer.php';
require_once 'classes/PHPWord/Element/Bookmark.php';
require_once 'classes/PHPWord/Element/Cell.php';
require_once 'classes/PHPWord/Element/Chart.php';
require_once 'classes/PHPWord/Element/Text.php';
require_once 'classes/PHPWord/Element/CheckBox.php';
require_once 'classes/PHPWord/Element/Footnote.php';
require_once 'classes/PHPWord/Element/Endnote.php';
require_once 'classes/PHPWord/Element/Field.php';
require_once 'classes/PHPWord/Element/Footer.php';
require_once 'classes/PHPWord/Element/FormField.php';
require_once 'classes/PHPWord/Element/Header.php';
require_once 'classes/PHPWord/Element/Image.php';
require_once 'classes/PHPWord/Element/Line.php';
require_once 'classes/PHPWord/Element/Link.php';
require_once 'classes/PHPWord/Element/ListItem.php';
require_once 'classes/PHPWord/Element/TextRun.php';
require_once 'classes/PHPWord/Element/ListItemRun.php';
require_once 'classes/PHPWord/Element/Object.php';
require_once 'classes/PHPWord/Element/PageBreak.php';
require_once 'classes/PHPWord/Element/PreserveText.php';
require_once 'classes/PHPWord/Element/Row.php';
require_once 'classes/PHPWord/Element/SDT.php';
require_once 'classes/PHPWord/Element/Section.php';
require_once 'classes/PHPWord/Element/Shape.php';
require_once 'classes/PHPWord/Element/TOC.php';
require_once 'classes/PHPWord/Element/Table.php';
require_once 'classes/PHPWord/Element/TextBox.php';
require_once 'classes/PHPWord/Element/TextBreak.php';
require_once 'classes/PHPWord/Element/Title.php';

require_once 'classes/PHPWord/Collection/AbstractCollection.php';
require_once 'classes/PHPWord/Collection/Bookmarks.php';
require_once 'classes/PHPWord/Collection/Charts.php';
require_once 'classes/PHPWord/Collection/Endnotes.php';
require_once 'classes/PHPWord/Collection/Footnotes.php';
require_once 'classes/PHPWord/Collection/Titles.php';

require_once 'classes/PHPWord/Exception/Exception.php';
require_once 'classes/PHPWord/Exception/CopyFileException.php';
require_once 'classes/PHPWord/Exception/CreateTemporaryFileException.php';
require_once 'classes/PHPWord/Exception/InvalidImageException.php';
require_once 'classes/PHPWord/Exception/InvalidObjectException.php';
require_once 'classes/PHPWord/Exception/InvalidStyleException.php';
require_once 'classes/PHPWord/Exception/UnsupportedImageTypeException.php';

require_once 'classes/PHPWord/Metadata/Compatibility.php';
require_once 'classes/PHPWord/Metadata/DocInfo.php';
require_once 'classes/PHPWord/Metadata/Protection.php';

require_once 'classes/PHPWord/Reader/ReaderInterface.php';
require_once 'classes/PHPWord/Reader/AbstractReader.php';
require_once 'classes/PHPWord/Reader/HTML.php';
require_once 'classes/PHPWord/Reader/MsDoc.php';
require_once 'classes/PHPWord/Reader/ODText.php';

require_once 'classes/PHPWord/Reader/Word2007.php';
require_once 'classes/PHPWord/Reader/Word2007/AbstractPart.php';
require_once 'classes/PHPWord/Reader/Word2007/DocPropsCore.php';
require_once 'classes/PHPWord/Reader/Word2007/DocPropsApp.php';
require_once 'classes/PHPWord/Reader/Word2007/DocPropsCustom.php';
require_once 'classes/PHPWord/Reader/Word2007/Document.php';
require_once 'classes/PHPWord/Reader/Word2007/Footnotes.php';
require_once 'classes/PHPWord/Reader/Word2007/Endnotes.php';
require_once 'classes/PHPWord/Reader/Word2007/Numbering.php';
require_once 'classes/PHPWord/Reader/Word2007/Styles.php';

require_once 'classes/PHPWord/Reader/ODText/AbstractPart.php';
require_once 'classes/PHPWord/Reader/ODText/Content.php';
require_once 'classes/PHPWord/Reader/ODText/Meta.php';
require_once 'classes/PHPWord/Reader/RTF.php';
require_once 'classes/PHPWord/Reader/RTF/Document.php';

require_once 'classes/PHPWord/Shared/Converter.php';
require_once 'classes/PHPWord/Shared/Drawing.php';
require_once 'classes/PHPWord/Shared/Font.php';
require_once 'classes/PHPWord/Shared/Html.php';
require_once 'classes/PHPWord/Shared/OLERead.php';
//require_once 'classes/PHPWord/Shared/PCLZip/pclzip.lib.php';
require_once 'classes/PHPWord/Shared/String.php';
require_once 'classes/PHPWord/Shared/XMLReader.php';
require_once 'classes/PHPWord/Shared/XMLWriter.php';
require_once 'classes/PHPWord/Shared/ZipArchive.php';

require_once 'classes/PHPWord/Style/AbstractStyle.php';
require_once 'classes/PHPWord/Style/Alignment.php';
require_once 'classes/PHPWord/Style/Border.php';
require_once 'classes/PHPWord/Style/Cell.php';
require_once 'classes/PHPWord/Style/Chart.php';
require_once 'classes/PHPWord/Style/Extrusion.php';
require_once 'classes/PHPWord/Style/Fill.php';
require_once 'classes/PHPWord/Style/Font.php';
require_once 'classes/PHPWord/Style/Frame.php';
require_once 'classes/PHPWord/Style/Image.php';
require_once 'classes/PHPWord/Style/Indentation.php';
require_once 'classes/PHPWord/Style/Line.php';
require_once 'classes/PHPWord/Style/LineNumbering.php';
require_once 'classes/PHPWord/Style/ListItem.php';
require_once 'classes/PHPWord/Style/Numbering.php';
require_once 'classes/PHPWord/Style/NumberingLevel.php';
require_once 'classes/PHPWord/Style/Outline.php';
require_once 'classes/PHPWord/Style/Paper.php';
require_once 'classes/PHPWord/Style/Paragraph.php';
require_once 'classes/PHPWord/Style/Row.php';
require_once 'classes/PHPWord/Style/Section.php';
require_once 'classes/PHPWord/Style/Shading.php';
require_once 'classes/PHPWord/Style/Shadow.php';
require_once 'classes/PHPWord/Style/Shape.php';
require_once 'classes/PHPWord/Style/Spacing.php';
require_once 'classes/PHPWord/Style/Tab.php';
require_once 'classes/PHPWord/Style/TOC.php';
require_once 'classes/PHPWord/Style/Table.php';
require_once 'classes/PHPWord/Style/TextBox.php';

require_once 'classes/PHPWord/Writer/WriterInterface.php';
require_once 'classes/PHPWord/Writer/AbstractWriter.php';
require_once 'classes/PHPWord/Writer/HTML.php';

require_once 'classes/PHPWord/Writer/HTML/Element/AbstractElement.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Container.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Footnote.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Endnote.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Text.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Image.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Link.php';
require_once 'classes/PHPWord/Writer/HTML/Element/ListItem.php';
require_once 'classes/PHPWord/Writer/HTML/Element/TextBreak.php';
require_once 'classes/PHPWord/Writer/HTML/Element/PageBreak.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Table.php';
require_once 'classes/PHPWord/Writer/HTML/Element/TextRun.php';
require_once 'classes/PHPWord/Writer/HTML/Element/Title.php';

require_once 'classes/PHPWord/Writer/HTML/Part/AbstractPart.php';
require_once 'classes/PHPWord/Writer/HTML/Part/Body.php';
require_once 'classes/PHPWord/Writer/HTML/Part/Head.php';

require_once 'classes/PHPWord/Writer/HTML/Style/AbstractStyle.php';
require_once 'classes/PHPWord/Writer/HTML/Style/Font.php';
require_once 'classes/PHPWord/Writer/HTML/Style/Generic.php';
require_once 'classes/PHPWord/Writer/HTML/Style/Image.php';
require_once 'classes/PHPWord/Writer/HTML/Style/Paragraph.php';

require_once 'classes/PHPWord/Writer/ODText.php';

require_once 'classes/PHPWord/Writer/Word2007/Element/AbstractElement.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Bookmark.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Chart.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Text.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/CheckBox.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Container.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Footnote.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Endnote.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Field.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/FormField.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Image.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Line.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Link.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/ListItem.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/ListItemRun.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Object.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/PageBreak.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/PreserveText.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/SDT.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Shape.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/TOC.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Table.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/TextBox.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/TextBreak.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/TextRun.php';
require_once 'classes/PHPWord/Writer/Word2007/Element/Title.php';

require_once 'classes/PHPWord/Writer/ODText/Element/AbstractElement.php';
require_once 'classes/PHPWord/Writer/ODText/Element/Container.php';
require_once 'classes/PHPWord/Writer/ODText/Element/Image.php';
require_once 'classes/PHPWord/Writer/ODText/Element/Link.php';
require_once 'classes/PHPWord/Writer/ODText/Element/Table.php';
require_once 'classes/PHPWord/Writer/ODText/Element/Text.php';
require_once 'classes/PHPWord/Writer/ODText/Element/TextBreak.php';
require_once 'classes/PHPWord/Writer/ODText/Element/TextRun.php';
require_once 'classes/PHPWord/Writer/ODText/Element/Title.php';

require_once 'classes/PHPWord/Writer/Word2007/Part/AbstractPart.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Chart.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/ContentTypes.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/DocPropsApp.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/DocPropsCore.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/DocPropsCustom.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Document.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Footnotes.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Endnotes.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/FontTable.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Footer.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Header.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Numbering.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Rels.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/RelsDocument.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/RelsPart.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Settings.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Styles.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/Theme.php';
require_once 'classes/PHPWord/Writer/Word2007/Part/WebSettings.php';

require_once 'classes/PHPWord/Writer/ODText/Part/AbstractPart.php';
require_once 'classes/PHPWord/Writer/ODText/Part/Content.php';
require_once 'classes/PHPWord/Writer/ODText/Part/Manifest.php';
require_once 'classes/PHPWord/Writer/ODText/Part/Meta.php';
require_once 'classes/PHPWord/Writer/ODText/Part/Mimetype.php';
require_once 'classes/PHPWord/Writer/ODText/Part/Styles.php';

require_once 'classes/PHPWord/Writer/Word2007/Style/AbstractStyle.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Alignment.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Cell.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Extrusion.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Fill.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Font.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Frame.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Image.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Indentation.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Line.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/LineNumbering.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/MarginBorder.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Outline.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Paragraph.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Row.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Section.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Shading.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Shadow.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Shape.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Spacing.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Tab.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/Table.php';
require_once 'classes/PHPWord/Writer/Word2007/Style/TextBox.php';

require_once 'classes/PHPWord/Writer/ODText/Style/AbstractStyle.php';
require_once 'classes/PHPWord/Writer/ODText/Style/Font.php';
require_once 'classes/PHPWord/Writer/ODText/Style/Image.php';
require_once 'classes/PHPWord/Writer/ODText/Style/Paragraph.php';
require_once 'classes/PHPWord/Writer/ODText/Style/Section.php';
require_once 'classes/PHPWord/Writer/ODText/Style/Table.php';

require_once 'classes/PHPWord/Writer/PDF.php';
require_once 'classes/PHPWord/Writer/PDF/AbstractRenderer.php';
require_once 'classes/PHPWord/Writer/PDF/DomPDF.php';
require_once 'classes/PHPWord/Writer/PDF/MPDF.php';
require_once 'classes/PHPWord/Writer/PDF/TCPDF.php';

require_once 'classes/PHPWord/Writer/RTF.php';

require_once 'classes/PHPWord/Writer/RTF/Element/AbstractElement.php';
require_once 'classes/PHPWord/Writer/RTF/Element/Container.php';
require_once 'classes/PHPWord/Writer/RTF/Element/Image.php';
require_once 'classes/PHPWord/Writer/RTF/Element/Link.php';
require_once 'classes/PHPWord/Writer/RTF/Element/Text.php';
require_once 'classes/PHPWord/Writer/RTF/Element/ListItem.php';
require_once 'classes/PHPWord/Writer/RTF/Element/PageBreak.php';
require_once 'classes/PHPWord/Writer/RTF/Element/Table.php';
require_once 'classes/PHPWord/Writer/RTF/Element/TextBreak.php';
require_once 'classes/PHPWord/Writer/RTF/Element/TextRun.php';
require_once 'classes/PHPWord/Writer/RTF/Element/Title.php';

require_once 'classes/PHPWord/Writer/RTF/Part/AbstractPart.php';
require_once 'classes/PHPWord/Writer/RTF/Part/Document.php';
require_once 'classes/PHPWord/Writer/RTF/Part/Header.php';

require_once 'classes/PHPWord/Writer/RTF/Style/AbstractStyle.php';
require_once 'classes/PHPWord/Writer/RTF/Style/Border.php';
require_once 'classes/PHPWord/Writer/RTF/Style/Font.php';
require_once 'classes/PHPWord/Writer/RTF/Style/Paragraph.php';
require_once 'classes/PHPWord/Writer/RTF/Style/Section.php';

require_once 'classes/PHPWord/Writer/Word2007.php';

require_once 'classes/PHPWord/IOFactory.php';
require_once 'classes/PHPWord/Media.php';
require_once 'classes/PHPWord/Autoloader.php';
require_once 'classes/PHPWord/PhpWord.php';
require_once 'classes/PHPWord/Settings.php';
require_once 'classes/PHPWord/Style.php';
require_once 'classes/PHPWord/TemplateProcessor.php';
require_once 'classes/PHPWord/Template.php';
?>