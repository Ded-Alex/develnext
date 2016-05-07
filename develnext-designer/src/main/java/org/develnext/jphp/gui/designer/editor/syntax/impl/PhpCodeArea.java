package org.develnext.jphp.gui.designer.editor.syntax.impl;

import org.antlr.v4.runtime.*;
import org.develnext.jphp.gui.designer.editor.syntax.AbstractCodeArea;
import org.develnext.jphp.gui.designer.editor.syntax.CodeAreaGutterNote;
import org.develnext.lexer.php.PHPLexer;
import org.develnext.lexer.php.PHPParser;
import org.fxmisc.richtext.StyleSpansBuilder;

import java.util.Collection;
import java.util.Collections;


public class PhpCodeArea extends AbstractCodeArea {
    private final BaseErrorListener errorListener = new BaseErrorListener() {
        @Override
        public void syntaxError(Recognizer<?, ?> recognizer, Object offendingSymbol, int line, int charPositionInLine, String msg, RecognitionException e) {
            getGutter().addNote(line, new CodeAreaGutterNote("error", msg));
        }
    };

    public PhpCodeArea() {
        super();
        setStylesheet(AbstractCodeArea.class.getResource("PhpCodeArea.css").toExternalForm());
    }

    private static Collection<String> getStyleOfToken(Token token) {
        switch (token.getType()) {
            case PHPParser.CommentEnd:
            case PHPParser.HtmlComment:
            case PHPParser.MultiLineComment:
            case PHPParser.SingleLineComment:
            case PHPParser.PHPEndSingleLineComment:
            case PHPParser.Comment:
                return Collections.singletonList("comment");

            case PHPParser.HtmlHex:
                return Collections.singletonList("color");

            case PHPParser.DoubleQuoteString:
            case PHPParser.BackQuoteString:
            case PHPParser.SingleQuoteString:
            case PHPParser.HereDocText:
            case PHPParser.StartHereDoc:
            case PHPParser.StartNowDoc:
            case PHPParser.StringType:
                return Collections.singletonList("string");

            case PHPParser.Numeric:
                return Collections.singletonList("number");

            case PHPParser.VarName:
                return Collections.singletonList("variable");

            case PHPParser.Abstract:
            case PHPParser.As:
            case PHPParser.Array:
            case PHPParser.BooleanAnd:
            case PHPParser.BooleanOr:
            case PHPParser.BooleanConstant:
            case PHPParser.Break:
            case PHPParser.Continue:
            case PHPParser.Callable:
            case PHPParser.Case:
            case PHPParser.Catch:
            case PHPParser.Class:
            case PHPParser.If:
            case PHPParser.Else:
            case PHPParser.ElseIf:
            case PHPParser.Switch:
            case PHPParser.While:
            case PHPParser.Do:
            case PHPParser.Const:
            case PHPParser.Public:
            case PHPParser.Var:
            case PHPParser.Protected:
            case PHPParser.Static:
            case PHPParser.Print:
            case PHPParser.PHPStart:
            case PHPParser.Clone:
            case PHPParser.Echo:
            case PHPParser.Empty:
            case PHPParser.IsSet:
            case PHPParser.Eval:
            case PHPParser.Return:
            case PHPParser.Yield:
            case PHPParser.Null:
            case PHPParser.Finally:
            case PHPParser.Try:
            case PHPParser.Include:
            case PHPParser.Require:
            case PHPParser.IncludeOnce:
            case PHPParser.RequireOnce:
            case PHPParser.Exit:
            case PHPParser.Foreach:
            case PHPParser.For:
            case PHPParser.Use:
            case PHPParser.Namespace:
            case PHPParser.Trait:
            case PHPParser.Interface:
            case PHPParser.Extends:
            case PHPParser.Implements:
            case PHPParser.Function:
            case PHPParser.Global:
            case PHPParser.EndDeclare:
            case PHPParser.EndFor:
            case PHPParser.EndForeach:
            case PHPParser.EndIf:
            case PHPParser.EndSwitch:
            case PHPParser.EndWhile:
            case PHPParser.Goto:
            case PHPParser.InstanceOf:
            case PHPParser.InsteadOf:
            case PHPParser.List:
            case PHPParser.New:
            case PHPParser.Throw:
            case PHPParser.Unset:
                return Collections.singletonList("keyword");

            case PHPParser.Label:
                return Collections.singletonList("label");

            case PHPParser.Colon:
            case PHPParser.Comma:
            case PHPParser.OpenCurlyBracket:
            case PHPParser.OpenRoundBracket:
            case PHPParser.OpenSquareBracket:
            case PHPParser.CloseCurlyBracket:
            case PHPParser.CloseRoundBracket:
            case PHPParser.CloseSquareBracket:
                return Collections.singletonList("control");

            case PHPParser.Plus:
            case PHPParser.PlusEqual:
            case PHPParser.Minus:
            case PHPParser.MinusEqual:
            case PHPParser.ModEqual:
            case PHPParser.Percent:
            case PHPParser.Divide:
            case PHPParser.DivEqual:
            case PHPParser.MulEqual:
            case PHPParser.Eq:
            case PHPParser.IsNotEq:
            case PHPParser.Ellipsis:
            case PHPParser.Dec:
            case PHPParser.Inc:
            case PHPParser.IsEqual:
            case PHPParser.IsIdentical:
            case PHPParser.IsNoidentical:
            case PHPParser.ObjectOperator:
            case PHPParser.Less:
            case PHPParser.Greater:
            case PHPParser.Ampersand:
            case PHPParser.Bang:
            case PHPParser.Pipe:
            case PHPParser.Asterisk:
            case PHPParser.Tilde:
            case PHPParser.Dot:
            case PHPParser.QuestionMark:
            case PHPParser.DoubleArrow:
            case PHPParser.Concaequal:
            case PHPParser.ShiftLeftEqual:
            case PHPParser.ShiftRightEqual:
            case PHPParser.AndEqual:
            case PHPParser.OrEqual:
            case PHPParser.XorEqual:
            case PHPParser.ShiftLeft:
            case PHPParser.ShiftRight:
            case PHPParser.DoubleColon:
                return Collections.singletonList("operator");

            case PHPParser.SemiColon:
                return Collections.singletonList("semicolon");

            default:
                return Collections.emptyList();
        }
    }

    @Override
    protected void computeHighlighting(StyleSpansBuilder<Collection<String>> spansBuilder, String text) {
        ANTLRInputStream inputStream = new ANTLRInputStream(text);
        PHPLexer lex = new PHPLexer(inputStream);
        //lex.addErrorListener(errorListener);

        int lastEnd = 0;
        for (Token token : lex.getAllTokens()) {
            int startIndex = token.getStartIndex();

            if (token.getType() == PHPParser.Comment) {
                if (text.charAt(startIndex - 1) == '#') {
                    startIndex -= 1;
                } else if (text.charAt(startIndex - 1) == '/' && text.charAt(startIndex - 2) == '/') {
                    startIndex -= 2;
                }
            }

            int spacer = startIndex - lastEnd;

            if (spacer > 0) {
                spansBuilder.add(Collections.emptyList(), spacer);
            }

            Collection<String> styleOfToken = getStyleOfToken(token);

            int gap = token.getStopIndex() - startIndex + 1;
            spansBuilder.add(styleOfToken, gap);

            lastEnd = token.getStopIndex() + 1;
        }

        lex.reset();
        PHPParser cssParser = new PHPParser(new CommonTokenStream(lex));
        cssParser.addErrorListener(errorListener);
        cssParser.htmlDocument();
    }
}