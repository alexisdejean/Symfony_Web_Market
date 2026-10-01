<?php

namespace App\Form;

use App\Entity\Produits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [new NotBlank()],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.01'],
                'constraints' => [new NotBlank(), new PositiveOrZero()],
            ])
            ->add('stock', NumberType::class, [
                'label' => 'Stock',
                'required' => true,
                'scale' => 0,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => 1],
                'constraints' => [new NotBlank(), new PositiveOrZero()],
            ])
            ->add('couleur', TextType::class, [
                'label' => 'Couleur',
                'required' => true,
                'constraints' => [new NotBlank(), new Length(max: 255)],
                'attr' => ['list' => 'couleur-list', 'placeholder' => 'Saisir ou choisir une couleur'],
            ])
            ->add('matiere', TextType::class, [
                'label' => 'Matière',
                'required' => true,
                'constraints' => [new NotBlank(), new Length(max: 255)],
                'attr' => ['list' => 'matiere-list', 'placeholder' => 'Saisir ou choisir une matière'],
            ])
            ->add('forme', TextType::class, [
                'label' => 'Forme',
                'required' => true,
                'constraints' => [new NotBlank(), new Length(max: 255)],
                'attr' => ['list' => 'forme-list', 'placeholder' => 'Saisir ou choisir une forme'],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Veuillez sélectionner une image JPEG, PNG ou WebP.',
                    ),
                ],
                'attr' => ['accept' => 'image/*'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produits::class,
        ]);
    }
}
