export class Producto {

    constructor(id, categoria, nombre, descripcion, marca, talla, color, cantidad, precio) {

        this._id = id;
        this._categoria = categoria;
        this._nombre = nombre;
        this._descripcion = descripcion;
        this._marca = marca;
        this._talla = talla;
        this._color = color;
        this._cantidad = cantidad;
        this._precio = precio;
    }

    // GETTERS

    get id() {
        return this._id;
    }

    get categoria() {
        return this._categoria;
    }

    get nombre() {
        return this._nombre;
    }

    get descripcion() {
        return this._descripcion;
    }

    get marca() {
        return this._marca
    }

    get talla() {
        return this._talla;
    }

    get color() {
        return this._color;
    }

    get cantidad() {
        return this._cantidad;
    }

    get precio() {
        return this._precio;
    }


    // SETTERS

    set id(valor) {
        this._id = valor;
    }

     set categoria(valor) {
        this._categoria = valor;
    }

    set nombre(valor) {
        this._nombre = valor;
    }

    set descripcion(valor) {
        this._descripcion = valor;
    }

    set marca(valor) {
        this._marca = valor;
    }

    set talla(valor) {
        this._talla = valor;
    }

    set color(valor) {
        this._color = valor;
    }

    set cantidad(valor) {
        this._cantidad = valor;
    }

    set precio(valor) {
        this._precio = valor;
    }
}