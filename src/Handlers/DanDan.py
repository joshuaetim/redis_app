from tkinter import *
tk = Tk()
canvas = Canvas(tk, width=500, height=500)
objects = canvas.create_oval(0, 0, 25, 25, fill='indigo')
canvas.move(1, 250, 250)
def up(evt):
    if evt.keysym == 'Return':
        canvas.move(0, 1, -1)
canvas.bind_all('<KeyPress-Return>', up)