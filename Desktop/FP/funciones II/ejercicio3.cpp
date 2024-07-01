#include <iostream>
#include <cmath>
using namespace std;
int main() {
    double c1, c2;
    cout << "Ingrese el valor del primer cateto: ";cin >> c1;
    cout << "Ingrese el valor del segundo cateto: ";cin >> c2;
    double h=sqrt(pow(c1, 2) + pow(c2, 2));
    cout<<"La hipotenusa del triángulo rectángulo con catetos "<< 1<<" y " <<c2<< " es: "<<h<<endl;
    return 0;
}