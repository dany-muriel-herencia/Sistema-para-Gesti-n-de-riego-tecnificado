#include<iostream>
using namespace std;

void depositar(float);
void retirar(float);
void mostrarSaldo();

float saldo=0;//variable global

int main(){
    int op, a;
    do{
        system("cls");
        cout<<"Cajero automatico"<<endl;
        cout<<"1. Depositar"<<endl;
        cout<<"2. Retirar"<<endl;
        cout<<"3. Mostrar saldo"<<endl;
        cout<<"0. Salir"<<endl;
        cout<<"Elija una opcion: ";cin>>op;
        switch(op){
        case 0:
            cout<<"Saliendo ..."<<endl;
            system("pause");
        break;
        case 1:
            system("cls");
            cout<<"Ingrese el monto a depositar: ";cin>>a;
            depositar(a);
            mostrarSaldo();
            system("pause");
        break;
        case 2:
            system("cls");
            cout<<"Ingrese el monto a retirar: ";cin>>a;
            retirar(a);
            mostrarSaldo();
            system("pause");
        break;
        case 3:
            system("cls");
            mostrarSaldo(),
            system("pause");
        break;
        default:
            cout<<"Opcion no valida"<<endl;
            system("pause");
        break;
    }
    }while(op!=0);
    return 0;
}

void depositar(float monto){
    saldo = saldo+monto;
}

void retirar(float monto){
    saldo = saldo-monto;
}

void mostrarSaldo(){
    cout<<"El saldo actual es: "<<saldo<<endl;
}