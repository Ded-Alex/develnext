package org.develnext.jphp.ext.javafx.classes;

import javafx.beans.property.ReadOnlyProperty;
import javafx.beans.value.ChangeListener;
import javafx.beans.value.ObservableValue;
import javafx.collections.ObservableList;
import javafx.event.Event;
import javafx.geometry.Bounds;
import javafx.geometry.Orientation;
import javafx.geometry.Point2D;
import javafx.scene.DepthTest;
import javafx.scene.Node;
import javafx.scene.Parent;
import javafx.scene.effect.BlendMode;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.Pane;
import org.develnext.jphp.ext.javafx.JavaFXExtension;
import org.develnext.jphp.ext.javafx.support.EventProvider;
import org.develnext.jphp.ext.javafx.support.StyleManager;
import php.runtime.Memory;
import php.runtime.annotation.Reflection.*;
import php.runtime.env.Environment;
import php.runtime.env.TraceInfo;
import php.runtime.invoke.Invoker;
import php.runtime.lang.BaseWrapper;
import php.runtime.memory.ArrayMemory;
import php.runtime.memory.DoubleMemory;
import php.runtime.memory.StringMemory;
import php.runtime.memory.support.MemoryOperation;
import php.runtime.reflection.ClassEntity;

import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.util.Set;

@Abstract
@Name(JavaFXExtension.NS + "UXNode")
public class UXNode<T extends Node> extends BaseWrapper<Node> {
    interface WrappedInterface {
        @Property double baselineOffset();
        @Property BlendMode blendMode();
        @Property(hiddenInDebugInfo = true) @Nullable Node clip();
        @Property(hiddenInDebugInfo = true) Orientation contentBias();
        @Property(hiddenInDebugInfo = true) DepthTest depthTest();
        @Property String id();
        @Property("x") double layoutX();
        @Property("y") double layoutY();
        @Property(hiddenInDebugInfo = true) double opacity();

        @Property double rotate();

        @Property double scaleX();
        @Property double scaleY();
        @Property double scaleZ();

        @Property String style();

        @Property double translateX();
        @Property double translateY();
        @Property double translateZ();

        @Property boolean cache();
        @Property boolean disable();
        @Property boolean disabled();
        @Property boolean focused();
        @Property boolean focusTraversable();
        @Property boolean hover();
        @Property boolean managed();
        @Property boolean mouseTransparent();
        @Property boolean pickOnBounds();
        @Property boolean pressed();
        @Property boolean resizable();
        @Property boolean visible();

        @Property("classes") ObservableList<String> styleClass();

        @Property(hiddenInDebugInfo = true) @Nullable Object userData();

        void autosize();
        boolean contains(double localX, double localY);

        void relocate(double x, double y);
        void resize(double width, double height);
        void startFullDrag();

        double maxHeight(double width);
        double maxWidth(double height);
        double minHeight(double width);
        double minWidth(double height);

        double prefHeight(double width);
        double prefWidth(double height);

        void toBack();
        void toFront();

        void requestFocus();
    }

    protected final StyleManager styleManager = new StyleManager(this);


    public UXNode(Environment env, T wrappedObject) {
        super(env, wrappedObject);
    }

    public UXNode(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Override
    @SuppressWarnings("unchecked")
    public T getWrappedObject() {
        return (T) super.getWrappedObject();
    }

    public UXNode getRealObject() throws NoSuchMethodException, IllegalAccessException, InvocationTargetException, InstantiationException {
        Class<? extends BaseWrapper> wrapperClass = MemoryOperation.getWrapper(getWrappedObject().getClass());
        if (wrapperClass != null) {
            return (UXNode) wrapperClass
                    .getConstructor(Environment.class, getWrappedObject().getClass())
                    .newInstance(getEnvironment(), getWrappedObject());
        } else {
            return this;
        }
    }

    @Getter
    public double getScreenX() {
        double layoutX = getWrappedObject().getLayoutX();

        Point2D pt = getWrappedObject().localToScreen(layoutX, 0);
        return pt.getX();
    }

    @Setter
    public void setScreenX(double value) {
        Point2D pt = getWrappedObject().screenToLocal(value, 0);
        getWrappedObject().setLayoutX(pt.getX());
    }

    @Getter
    public double getScreenY() {
        double layoutY = getWrappedObject().getLayoutY();

        Point2D pt = getWrappedObject().localToScreen(0, layoutY);
        return pt.getY();
    }

    @Setter
    public void setScreenY(double value) {
        Point2D pt = getWrappedObject().screenToLocal(0, value);
        getWrappedObject().setLayoutY(pt.getY());
    }

    @Getter(hiddenInDebugInfo = true)
    public String getClassesString() {
        StringBuilder sb = new StringBuilder();

        for (String s : getWrappedObject().getStyleClass()) {
            sb.append(s.trim()).append(" ");
        }

        return sb.toString();
    }

    @Setter
    public void setClassesString(String value) {
        String[] strings = value.split(" ");

        getWrappedObject().getStyleClass().clear();
        getWrappedObject().getStyleClass().addAll(strings);
    }

    @Getter
    public boolean getEnabled() {
        return !getWrappedObject().isDisable();
    }

    @Setter
    public void setEnabled(boolean value) {
        getWrappedObject().setDisable(!value);
    }

    @Getter(hiddenInDebugInfo = true)
    protected double[] getPosition() {
        return new double[] { getWrappedObject().getLayoutX(), getWrappedObject().getLayoutY() };
    }

    @Setter
    protected void setPosition(double[] value) {
        if (value.length >= 2) {
            getWrappedObject().setLayoutX(value[0]);
            getWrappedObject().setLayoutY(value[1]);
        }
    }

    @Getter(hiddenInDebugInfo = true)
    protected double[] getSize() {
        Bounds bounds = getWrappedObject().getLayoutBounds();
        return new double[] { bounds.getWidth(), bounds.getHeight() };
    }

    @Setter
    protected void setSize(double[] size) {
        if (size.length >= 2) {
            getWrappedObject().prefWidth(size[0]);
            getWrappedObject().prefHeight(size[1]);
        }
    }

    @Getter
    protected double getWidth() {
        return getWrappedObject().getLayoutBounds().getWidth();
    }

    @Setter
    protected void setWidth(double v) {
        getWrappedObject().prefWidth(v);
    }

    @Getter
    protected double getHeight() {
        return getWrappedObject().getLayoutBounds().getHeight();
    }

    @Setter
    protected void setHeight(double v) {
        getWrappedObject().prefHeight(v);
    }

    @Setter
    public void setLeftAnchor(Memory v) {
        AnchorPane.setLeftAnchor(getWrappedObject(), v.isNull() ? null : v.toDouble());
    }

    @Getter
    public Memory getLeftAnchor() {
        Double anchor = AnchorPane.getLeftAnchor(getWrappedObject());
        return anchor == null ? Memory.NULL : DoubleMemory.valueOf(anchor);
    }

    @Setter
    public void setRightAnchor(Memory v) {
        AnchorPane.setRightAnchor(getWrappedObject(), v.isNull() ? null : v.toDouble());
    }

    @Getter
    public Memory getRightAnchor() {
        Double anchor = AnchorPane.getRightAnchor(getWrappedObject());
        return anchor == null ? Memory.NULL : DoubleMemory.valueOf(anchor);
    }

    @Setter
    public void setTopAnchor(Memory v) {
        AnchorPane.setTopAnchor(getWrappedObject(), v.isNull() ? null : v.toDouble());
    }

    @Getter
    public Memory getTopAnchor() {
        Double anchor = AnchorPane.getTopAnchor(getWrappedObject());
        return anchor == null ? Memory.NULL : DoubleMemory.valueOf(anchor);
    }

    @Setter
    public void setBottomAnchor(Memory v) {
        AnchorPane.setBottomAnchor(getWrappedObject(), v.isNull() ? null : v.toDouble());
    }

    @Getter
    public Memory getBottomAnchor() {
        Double anchor = AnchorPane.getBottomAnchor(getWrappedObject());
        return anchor == null ? Memory.NULL : DoubleMemory.valueOf(anchor);
    }

    @Signature
    public void show() {
        getWrappedObject().setVisible(true);
    }

    @Signature
    public void hide() {
        getWrappedObject().setVisible(false);
    }

    @Signature
    public void toggle() {
        getWrappedObject().setVisible(!getWrappedObject().isVisible());
    }

    @Signature
    @SuppressWarnings("unchecked")
    public Memory lookup(Environment env, TraceInfo trace, String selector) throws Throwable {
        Node result = getWrappedObject().lookup(selector);

        if (result == null) {
            return null;
        }

        return MemoryOperation.get(result.getClass(), null).unconvert(env, trace, result);
    }

    @Signature
    @SuppressWarnings("unchecked")
    public Memory lookupAll(Environment env, TraceInfo trace, String selector) throws Throwable {
        Set<Node> result = getWrappedObject().lookupAll(selector);

        ArrayMemory r = new ArrayMemory();

        for (Node node : result) {
            Memory el = MemoryOperation.get(node.getClass(), null).unconvert(env, trace, node);
            r.add(el);
        }

        return r.toConstant();
    }

    @Getter(hiddenInDebugInfo = true)
    protected Memory getParent(Environment env) {
        return Memory.wrap(env, getWrappedObject().getParent());
    }

    @Getter(hiddenInDebugInfo = true)
    protected UXScene getScene(Environment env) {
        if (getWrappedObject().getScene() == null) {
            return null;
        }

        return new UXScene(env, getWrappedObject().getScene());
    }

    @Signature
    public Memory css(Environment env, Memory... args) {
        if (args == null || args.length == 0) {
            return ArrayMemory.ofStringMap(styleManager.all()).toConstant();
        } else if (args.length == 1) {
            if (args[0].isArray()) {
                styleManager.set(args[0].toValue(ArrayMemory.class).toStringMap());
            } else {
                return StringMemory.valueOf(styleManager.get(args[0].toString()));
            }
        } else {
            styleManager.set(args[0].toString(), args[1].toString());
        }

        return Memory.NULL;
    }

    @Signature
    @SuppressWarnings("unchecked")
    public void on(String event, Invoker invoker, String group) {
        EventProvider eventProvider = EventProvider.get(getWrappedObject(), event);

        if (eventProvider != null) {
            eventProvider.on(getWrappedObject(), event, group, invoker);
        } else {
            throw new IllegalArgumentException("Unable to find the '"+event+"' event type");
        }
    }

    @Signature
    public void watch(final String property, final Invoker invoker) throws InvocationTargetException, IllegalAccessException {
        String name = property + "Property";

        Class<? extends Node> aClass = getWrappedObject().getClass();

        try {
            Method method = aClass.getMethod(name);

            ReadOnlyProperty bindProperty = (ReadOnlyProperty) method.invoke(getWrappedObject());

            bindProperty.addListener(new ChangeListener() {
                @Override
                public void changed(ObservableValue observable, Object oldValue, Object newValue) {
                    invoker.callAny(UXNode.this, property, oldValue, newValue);
                }
            });
        } catch (NoSuchMethodException | ClassCastException e) {
            throw new IllegalArgumentException("Unable to find the '" + property + "' property for watching");
        }
    }

    @Signature
    public void on(String event, Invoker invoker) {
        on(event, invoker, "general");
    }

    @Signature
    @SuppressWarnings("unchecked")
    public void off(String event, @Nullable String group) {
        EventProvider eventProvider = EventProvider.get(getWrappedObject(), event);

        if (eventProvider != null) {
            eventProvider.off(getWrappedObject(), event, group);
        } else {
            throw new IllegalArgumentException("Unable to find the '"+event+"' event type");
        }
    }

    @Signature
    public void off(String event) {
        off(event, null);
    }

    @Signature
    public void trigger(String event, @Nullable Event e) {
        EventProvider eventProvider = EventProvider.get(getWrappedObject(), event);

        if (eventProvider != null) {
            eventProvider.trigger(getWrappedObject(), event, e);
        } else {
            throw new IllegalArgumentException("Unable to find the '"+event+"' event type");
        }
    }

    @Signature
    public boolean free() {
        Parent parent = getWrappedObject().getParent();

        if (parent instanceof Pane) {
            return ((Pane) parent).getChildren().remove(getWrappedObject());
        }

        return false;
    }

    @Override
    public int getPointer() {
        return getWrappedObject().hashCode();
    }
}
